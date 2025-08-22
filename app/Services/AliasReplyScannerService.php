<?php

namespace App\Services;

use Exception;
use App\Models\Team;
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
        $sanitized = strip_tags($body, "<br>");
        return $sanitized;
    }
    public function checkBodyIsSensible($text)
    {
        // Remove punctuation and special characters
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);

        // Tokenize the text into words
        $words = str_word_count($text, format: 1);

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

        $this->notifyDarManagesOfNewMessage($threadId);
        $this->notifyUserOfDarResponse($threadId);

        unset($body);
        unset($from);

        return $enquiryMessage;
    }

    public function deleteMessage($message)
    {
        return $message->delete($expunge = true);
    }

    public function notifyUserOfDarResponse($threadId) {
        $enquiryThread = EnquiryThread::where('id', $threadId)->first();
        $user = User::where([
            'id' => $enquiryThread->user_id,
        ])->first();
        $enquiryMessage = EnquiryMessage::where([
            'thread_id' => $threadId,
        ])->latest()->first();

        $uniqueKey = $enquiryThread->unique_key;
        $usersToNotify[] = [
                        'user' => $user->toArray(),
                        'team' => null,
                    ];

        $usersToNotify[0]['user']['email'] = $enquiryThread->user_preferred_email === "primary" ? $user->email : $user->secondary_email;

        $payload = [
            'thread' => [
                'user_id' => $enquiryThread->user_id,
                'user_preferred_email' => $enquiryThread->user_preferred_email,
                'team_ids' => [$enquiryThread->team_id],
                'project_title' => $enquiryThread->project_title,
                'unique_key' => $uniqueKey,
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
        $this->sendEmail('dar.notifymessage', $payload, $usersToNotify, $enquiryThread->user_id, $body);

    }

    public function notifyDarManagesOfNewMessage($threadId)
    {
        $usersToNotify = [];

        $enquiryThread = EnquiryThread::where('id', $threadId)->first();

        $uniqueKey = $enquiryThread->unique_key;

        $usersToNotify = $this->getUsersByTeamIds([$enquiryThread->team_id], $enquiryThread->user_id, $enquiryThread->user_preferred_email);

        if (empty($usersToNotify)) {
            $this->loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;
            \Log::info(
                'EnquiryThread was created, but no custodian.dar.managers found to notify for thread ' . $threadId,
                $this->loggingContext,
            );
            return;
        }

        $enquiryMessage = EnquiryMessage::where([
            'thread_id' => $threadId,
        ])->latest()->first();

        $user = User::where([
            'id' => $enquiryThread->user_id,
        ])->first();

        $teamNames = [];
        $team = Team::where('id', $enquiryThread->team_id)->first();
        $teamNames[] = $team->name;

        $payload = [
            'thread' => [
                'user_id' => $enquiryThread->user_id,
                'user_preferred_email' => $enquiryThread->user_preferred_email,
                'team_ids' => [$enquiryThread->team_id],
                'team_names' => $teamNames,
                'project_title' => $enquiryThread->project_title,
                'unique_key' => $uniqueKey, // Not random, but should be unique
            ],
            'message' => [
                'from' => $enquiryMessage->from,
                'message_body' => [
                    '[[USER_FIRST_NAME]]' => $user->firstname,
                    '[[USER_LAST_NAME]]' => $user->lastname,
                    '[[USER_ORGANISATION]]' => $user->organisation,
                    '[[PROJECT_TITLE]]' => $enquiryThread->project_title,
                    '[[CURRENT_YEAR]]' => date(format: 'Y'),
                    '[[SENDER_NAME]]' => $enquiryMessage->from,
                ],
            ],
        ];

        $messageBody = $enquiryMessage->message_body;
        $lines = preg_split('/\r\n|\r|\n/', $messageBody);
        $cleanedText = implode("\n", array_filter($lines));
        $body = trim(str_replace('P {margin-top:0;margin-bottom:0;}', '', str_replace(["\r\n", "\n"], "<br/>", $cleanedText)));
        $this->sendEmail('dar.notifymessage', $payload, $usersToNotify, $enquiryThread->user_id, $body);

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
        list($username, $domain) = explode('@', string: $imapUsername);

        try {
            $template = EmailTemplate::where('identifier', $ident)->first();
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
                        'email' => $user['user']['email'],
                        'name' => $user['user']['name'],
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
