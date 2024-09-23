<?php

namespace App\AliasReplyScanner;

use Exception;
use CloudLogger;
use App\Models\Team;
use App\Models\User;
use App\Jobs\SendEmailJob;

use App\Models\EmailTemplate;
use App\Models\EnquiryThread;

use App\Models\EnquiryMessage;
use Webklex\PHPIMAP\ClientManager;

use EnquiriesManagementController as EMC;

class AliasReplyScanner
{
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
            return $this->checkBodyIsSensible($body);
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

        if(count($words) == 0) {
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

        unset($body);
        unset($from);

        return $enquiryMessage;
    }

    public function deleteMessage($message)
    {
        return $message->delete($expunge = true);
    }

    public function notifyDarManagesOfNewMessage($threadId)
    {
        $usersToNotify = [];

        $enquiryThread = EnquiryThread::where([
            'id' => $threadId,
        ])->first();

        $uniqueKey = $enquiryThread->unique_key;

        $driver = \DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Use BINARY operator
            $enquiryThreads = EnquiryThread::whereRaw('BINARY `unique_key` LIKE ?', ['%' . $uniqueKey . '%'])->get();
        } elseif ($driver === 'sqlite') { // for tests in sqlite
            // SQLite: Use COLLATE BINARY or GLOB
            $enquiryThreads = EnquiryThread::whereRaw('`unique_key` LIKE ? COLLATE BINARY', ['%' . $uniqueKey . '%'])->get();
        }

        $enquiryThreads = EnquiryThread::whereRaw('BINARY `unique_key` LIKE ?', [$uniqueKey])->get();

        foreach ($enquiryThreads as $eqTh) {
            $usersToNotify[] = EMC::determineDARManagersFromTeamId($eqTh->team_id, $eqTh->id);
        }

        if (empty($usersToNotify)) {
            CloudLogger::write([
                'action_type' => 'NOTIFY',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'EnquiryThread was created, but no custodian.dar.managers found to notify for thread ' .
                    $threadId,
            ]);

            return;
        }

        $enquiryMessage = EnquiryMessage::where([
            'thread_id' => $threadId,
        ])->latest()->first();

        $team = Team::where([
            'id' => $enquiryThread->team_id,
        ])->first();

        $user = User::where([
            'id' => $enquiryThread->user_id,
        ])->first();

        $payload = [
            'thread' => [
                'user_id' => $enquiryThread->user_id,
                'team_id' => $enquiryThread->team_id,
                'project_title' => $enquiryThread->project_title,
                'unique_key' => $uniqueKey, // Not random, but should be unique
            ],
            'message' => [
                'from' => $enquiryMessage->from,
                'message_body' => [
                    '[[TEAM_NAME]]' => $team->name,
                    '[[USER_FIRST_NAME]]' => $user->firstname,
                    '[[USER_LAST_NAME]]' => $user->lastname,
                    '[[USER_ORGANISATION]]' => $user->organisation,
                    '[[PROJECT_TITLE]]' => $enquiryThread->project_title,
                    '[[CURRENT_YEAR]]' => date('Y'),
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
            $enquiryThreads,
            $team,
            $user,
            $usersToNotify,
        );
    }

    public function sendEmail(string $ident, array $threadDetail, array $usersToNotify, int $userId, string $replyMessage): void
    {
        $something = null;

        try {
            $template = EmailTemplate::where('identifier', $ident)->first();
            $replacements = [
                '[[CURRENT_YEAR]]' => $threadDetail['message']['message_body']['[[CURRENT_YEAR]]'],
                '[[DAR_NOTIFY_MESSAGE]]' => $replyMessage,
            ];

            // TODO Add unique key to URL button. Future scope.
            foreach ($usersToNotify as $u) {
                if ($u === null) {
                    continue;
                }

                // In case for multiple users to notify, loop again for actual details.
                foreach ($u as $arr) {
                    $to = [
                        'to' => [
                            'email' => $arr['user']['email'],
                            'name' => $arr['user']['firstname'] . ' ' . $arr['user']['lastname'],
                        ],
                    ];

                    $from = 'devreply+' . $threadDetail['thread']['unique_key'] . '@healthdatagateway.org';
                    $something = SendEmailJob::dispatch($to, $template, $replacements, $from);
                }
            }
            unset(
                $template,
                $replacements,
                $from,
                $something,
            );
        } catch (Exception $e) {
            CloudLogger::write('ERROR reply email enquiry thread :: ' . json_encode($e->getMessage()));
        }
    }
}
