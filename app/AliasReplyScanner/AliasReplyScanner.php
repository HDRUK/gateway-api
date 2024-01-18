<?php

namespace App\AliasReplyScanner;


use App\Models\EnquiryMessages;
use App\Models\EnquiryThread;

use App\Models\Team;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;

use Webklex\PHPIMAP\ClientManager;

class AliasReplyScanner {

    public function getImapClient() {
        $cm = new ClientManager($options = []);
        $client = $cm->make([
            'host'          => env('ENQUIRY_IMAP_HOST'),
            'port'          => env('ENQUIRY_IMAP_PORT'),
            'encryption'    => env('ENQUIRY_IMAP_ENCRYPTION'),
            'validate_cert' => env('ENQUIRY_IMAP_VALIDATE_CERT'),
            'username'      => env('ENQUIRY_IMAP_USERNAME'),
            'password'      => env('ENQUIRY_IMAP_PASSWORD'),
            'protocol'      => env('ENQUIRY_IMAP_PROTOCOL'),
        ]);
        $client->connect();
        return $client;
    }

    public function getNewMessages(){
        $client = $this->getImapClient();
        $inbox = $client->getFolder(env('ENQUIRY_IMAP_INBOX_NAME'));
        return $inbox->messages()->all()->get();
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
        return EnquiryThread::where("unique_key",$alias)->first();
    }

    public function scrapeAndStoreContent($message,$threadId)
    {
        $body = $message->getHTMLBody();
        $from = $message->getFrom();
        $pos1 = strpos($from, '<');
        $pos2 = strpos($from, '>');
        $email = $from;
        if ($pos1 !== false && $pos2 !== false) {
            $email = substr($from, $pos1 + 1, $pos2 - $pos1 - 1);
        }

        $enquiryMessage = EnquiryMessages::create([
            "from" => $email,
            "message_body" => $body,
            "thread_id" => $threadId,
        ]);
        return $enquiryMessage;
    }
    public function deleteMessage($message){
        return $message->delete($expunge = true);
    }

    public function sendEmail($enquiryMessageId)
    {
        //get the message linked to the original thread
        $enquiryMessage = EnquiryMessages::with("thread")
                            ->where("id",$enquiryMessageId)
                            ->first();
        
        //find the team from the thread the message belongs to
        $teamId = $enquiryMessage->thread->team_id;
        $team = Team::with("teamUserRoles")
                ->where("id",$teamId)
                ->first();
       
        //from the team find all DAR managers 
        $darManagers = $team->teamUserRoles
                    ->where("role_name","custodian.dar.manager")
                    ->where("enabled",true);

        //email each dar manager that a new message has been received
        $sentEmails = array();
        foreach ($darManagers as $darManager){
      
            $userEmail = $darManager['user_preferred_email'] === 'secondary' ? $darManager['user_secondary_email'] : $darManager['user_email'];
            $userName = $darManager['user_name'];
      
            $to = [
                'to' => [
                    'email' => $userEmail,
                    'name' => $userName,
                ],
            ];
            
            $replacements = [
                '[[DAR_MANAGER_FIRSTNAME]]' => $darManager['user_firstname'],
                '[[ORIGINAL_MESSAGE_BODY]]' => $enquiryMessage->message_body,
                '[[ORIGINAL_MESSAGE_SENDER]]' => $enquiryMessage->from,
            ];

            //$this->info( json_encode($replacements, JSON_PRETTY_PRINT));

            // Note Calum 17/01/2024:
            //    - to be implemented once email design is done 
            // $template = EmailTemplate::where('identifier', '=', '.....')->first();
            // SendEmailJob::dispatch($to, $template, $replacements);
            $sentEmails[] = $userEmail;
        }
        return $sentEmails;
    }

}
