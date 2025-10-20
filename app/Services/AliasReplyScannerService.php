<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use App\Models\EnquiryThread;
use App\Models\EnquiryMessage;
use Webklex\PHPIMAP\ClientManager;
use App\Http\Traits\EnquiriesTrait;
use App\Http\Traits\LoggingContext;

class AliasReplyScannerService
{
    use EnquiriesTrait;
    use LoggingContext;

    private ?array $loggingContext = null;

    public function __construct()
    {
        $this->loggingContext = $this->getLoggingContext(\request());
        $this->loggingContext['method_name'] = class_basename($this);
    }

    public function getImapClient()
    {
        $cm = new ClientManager($options = []);
        $client = $cm->make(config('mail.mailers.ars.imap'));
        $client->connect();
        return $client;
    }

    public function getNewMessages()
    {
        $client = $this->getImapClient();
        $inbox = $client->getFolder(config('mail.mailers.ars.inbox'));
        return $inbox->messages()->all()->get();
    }

    public function getNewMessagesSafe()
    {
        $messages = $this->getNewMessages();
        return $messages->filter(function ($msg) {
            $body = $this->getSanitisedBody($msg);
            // 17/12/2024 - temporary turn off
            return true; //$this->checkBodyIsSensible($body);
        });
    }

    public function getSanitisedBody($message)
    {
        $body = $message->getHTMLBody();
        $sanitized = strip_tags($body);
        return $sanitized;
    }
    public function checkBodyIsSensible($text)
    {
        // Remove punctuation and special characters
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);

        // Tokenize the text into words
        $words = str_word_count($text, 1);

        if (count($words) == 0) {
            return false;
        }

        // Minimum number of words for sensible content
        $minWords = 5;

        // Calculate the average word length
        $averageWordLength = array_sum(array_map('strlen', $words)) / count($words);

        // Criteria for sensible content
        // note: we probably want to tune this and add in more spam checks
        $isSensible = count($words) >= $minWords && $averageWordLength >= 3;

        return $isSensible == true;
    }

    public function getAlias($message)
    {
        $toaddress = $message->get("toaddress");
        $plusPos = strpos($toaddress, '+');
        $atPos = strpos($toaddress, '@');
        if ($plusPos !== false && $atPos !== false) {
            $alias = substr($toaddress, $plusPos + 1, $atPos - $plusPos - 1);
            return $alias;
        }
    }

    public function getThread($alias)
    {
        return EnquiryThread::where("unique_key", $alias)->first();
    }

    public function scrapeAndStoreContent($message, $threadId)
    {
        $body = $this->getSanitisedBody($message);
        $from = $message->getFrom();
        $pos1 = strpos($from, '<');
        $pos2 = strpos($from, '>');
        $email = $from;
        if ($pos1 !== false && $pos2 !== false) {
            $email = substr($from, $pos1 + 1, $pos2 - $pos1 - 1);
        }

        $enquiryMessage = EnquiryMessage::create([
            "from" => $email,
            "message_body" => $body,
            "thread_id" => $threadId,
        ]);

        $this->notifyAllOfNewMessage($threadId, $email);

        unset($body);
        unset($from);

        return $enquiryMessage;
    }

    public function deleteMessage($message)
    {
        return $message->delete($expunge = true);
    }

    public function notifyAllOfNewMessage($threadId, $senderEmail)
    {
        $enquiryThread = EnquiryThread::where('id', $threadId)->first();

        // Get all custodian users
        $allUsersToNotify = $this->getUsersByTeamIds([$enquiryThread->team_id], $enquiryThread->user_id, $enquiryThread->user_preferred_email);

        if (empty($allUsersToNotify)) {
            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::info(
                'EnquiryThread exists, but no custodian.dar.managers found to notify for thread ' . $threadId,
                $this->loggingContext,
            );
            return;
        }

        // Add the enquiry user
        $user = User::where([
            'id' => $enquiryThread->user_id,
        ])->first();

        $user->preferred_email = $enquiryThread->user_preferred_email;

        $allUsersToNotify[] = [
            'user' => $user->toArray(),
            'team' => null,
        ];

        // Don't send an email to any user if it is their own message
        $usersToNotify = array_filter($allUsersToNotify, function ($entry) use ($senderEmail) {
            $emailToUse = ($entry['user']['preferred_email'] === 'primary') ? $entry['user']['email'] : $entry['user']['secondary_email'];
            return $emailToUse !== $senderEmail;
        });

        $enquiryMessage = EnquiryMessage::where([
            'thread_id' => $threadId,
        ])->latest()->first();

        $payload = [
            'thread' => [
                'user_id' => $enquiryThread->user_id,
                'user_preferred_email' => $enquiryThread->user_preferred_email,
                'team_ids' => [$enquiryThread->team_id],
                'project_title' => $enquiryThread->project_title,
                'unique_key' => $enquiryThread->unique_key,
            ],
            'message' => [
                'from' => $enquiryMessage->from,
                'message_body' => [
                    '[[USER_FIRST_NAME]]' => $user->firstname,
                    '[[USER_LAST_NAME]]' => $user->lastname,
                    '[[USER_ORGANISATION]]' => $user->organisation,
                    '[[PROJECT_TITLE]]' => $enquiryThread->project_title,
                    '[[CURRENT_YEAR]]' => date('Y'),
                    '[[SENDER_NAME]]' => $enquiryMessage->from,
                ],
            ],
        ];

        $messageBody = $enquiryMessage->message_body;
        $lines = preg_split('/\r\n|\r|\n/', $messageBody);
        $cleanedText = implode("\n", array_filter($lines));
        $body = trim(str_replace('P {margin-top:0;margin-bottom:0;}', '', str_replace(["\r\n", "\n"], "<br/>", $cleanedText)));
        // Bug to be fixed once templates are designed: this template is used even when the original thread was a General or Feasibility Enquiry.
        if ($enquiryThread->is_general_enquiry) {
            $this->sendEmail('dar.notifymessage', $payload, $usersToNotify, $enquiryThread->user_id, $body);
        } elseif ($enquiryThread->is_feasibility_enquiry) {
            $this->sendEmail('dar.notifymessage', $payload, $usersToNotify, $enquiryThread->user_id, $body);
        } elseif ($enquiryThread->is_dar_dialogue) {
            $this->sendEmail('dar.notifymessage', $payload, $usersToNotify, $enquiryThread->user_id, $body);
        } else {
            $this->sendEmail('dar.notifymessage', $payload, $usersToNotify, $enquiryThread->user_id, $body);
        }

        unset(
            $payload,
            $messageBody,
            $lines,
            $cleanedText,
            $body,
            $enquiryMessage,
            $enquiryThread,
            $user,
            $usersToNotify,
        );
    }

    public function sendEmail(string $ident, array $threadDetail, array $usersToNotify, int $userId, string $replyMessage): void
    {
        $something = null;

        $imapUsername = env('ARS_IMAP_USERNAME', 'devreply@healthdatagateway.org');
        list($username, $domain) = explode('@', $imapUsername);

        try {
            $template = EmailTemplate::where('identifier', $ident)->first();
            // TODO: once templates reworking is done, we will need to increase or standardise the replacements in use
            $replacements = array_merge(
                [
                    '[[CURRENT_YEAR]]' => $threadDetail['message']['message_body']['[[CURRENT_YEAR]]'],
                    '[[MESSAGE_BODY]]' => $replyMessage,
                    '[[DAR_NOTIFY_MESSAGE]]' => $replyMessage,
                ],
                $threadDetail['message']['message_body']
            );

            // TODO Add unique key to URL button. Future scope.
            foreach ($usersToNotify as $user) {
                $replacements['[[RECIPIENT_NAME]]'] = $user['user']['name'];
                $to = [
                    'to' => [
                        'email' => ($user['user']['preferred_email'] === 'primary') ? $user['user']['email'] : $user['user']['secondary_email'],
                        'name' => $user['user']['firstname'] ? $user['user']['firstname'] . ' ' . $user['user']['lastname'] : $user['user']['name'],
                    ],
                ];

                $from = $username . '+' . $threadDetail['thread']['unique_key'] . '@' . $domain;
                $something = SendEmailJob::dispatch($to, $template, $replacements, $from);
            }
            unset(
                $template,
                $replacements,
                $from,
                $something,
            );
        } catch (Exception $e) {
            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::info(
                'ERROR reply email enquiry thread :: ' . json_encode($e->getMessage()),
                $this->loggingContext,
            );
        }
    }
}
