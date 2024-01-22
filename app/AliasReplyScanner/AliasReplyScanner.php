<?php

namespace App\AliasReplyScanner;

use App\Models\EnquiryMessages;
use App\Models\EnquiryThread;

use App\Models\Team;

use App\Exceptions\AliasReplyScannerException;
use Exception;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;

use Webklex\PHPIMAP\ClientManager;

class AliasReplyScanner {

    public function getImapClient() {
        $cm = new ClientManager($options = []);
        $client = $cm->make(config('mail.mailers.ars.imap'));
        $client->connect();
        return $client;
    }

    public function getNewMessages(){
        $client = $this->getImapClient();
        $inbox = $client->getFolder(config('mail.mailers.ars.inbox'));
        return $inbox->messages()->all()->get();
    }

    public function getNewMessagesSafe(){
        $messages = $this->getNewMessages();
        return $messages->filter(function ($msg) {
            $body = $this->getSanitisedBody($msg);
            return $this->checkBodyIsSensible($body);
        });
    }

    public function getSanitisedBody($message){
        $body = $message->getHTMLBody();
        $sanitized = strip_tags($body);
        return $sanitized;
    }
    public function checkBodyIsSensible($text){
        // Remove punctuation and special characters
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);

        // Tokenize the text into words
        $words = str_word_count($text, 1);

        if(count($words)==0){
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
        return EnquiryThread::where("unique_key",$alias)->first();
    }

    public function scrapeAndStoreContent($message,$threadId)
    {
        $body = $this->getSanitisedBody($message);
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

        $this->notifyDarManagesOfNewMessage($enquiryMessage->id);

        return $enquiryMessage;
    }
    public function deleteMessage($message){
        return $message->delete($expunge = true);
    }


    public function notifyDarManagesOfNewMessage($enquiryMessageId)
    {
        $darManagers = $this->getDarManagersFromEnquiryMessage($enquiryMessageId);
        //email each dar manager that a new message has been received
        /*  

        Note Calum 22/01/2024:
        - this type of functionality needs to be moved to a seperate place/service
           to avoid spamming DAR managers whenever a new enquiry message is created
        - needs to be designed how this notification process will be 

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
        */

    }

    public function getDarManagersFromEnquiryMessage($enquiryMessageId)
    {
        $teamId = null;
        $team = null;
        $darManagers = null;

        //get the message linked to the original thread
        $enquiryMessage = EnquiryMessages::with("thread")
                            ->where("id",$enquiryMessageId)
                            ->first();
        
        //find the team from the thread the message belongs to
        try{
            $teamId = $enquiryMessage->thread->team_id;
        }catch (Exception $e) {
            throw new AliasReplyScannerException("Could not retrieve a team from the found enquiry thread.");
        }

        try{
            $team = Team::with("teamUserRoles")
                    ->where("id",$teamId)
                    ->first();

            //from the team find all DAR managers 
            $darManagers = $team->teamUserRoles
                        ->where("role_name","custodian.dar.manager")
                        ->where("enabled",true);

        }catch (Exception $e) {
            throw new AliasReplyScannerException("Could not retrieve the team (".$teamId.") and teamUserRoles");
        }
        return $darManagers;
    }

}
