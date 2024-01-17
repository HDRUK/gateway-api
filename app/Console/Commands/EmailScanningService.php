<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\EnquiryMessages;
use App\Models\EnquiryThread;

use App\Models\Team;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;

use Webklex\PHPIMAP\ClientManager;
use App\Http\Traits\TeamTransformation;


class EmailScanningService extends Command
{
    use TeamTransformation;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-scanning-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

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

        $inbox = $client->getFolder(env('ENQUIRY_IMAP_INBOX_NAME'));
        $messages = $inbox->messages()->all()->get();
        $this->info("Found ".count($messages)." emails to process");
        $nUnread = 0;
        foreach($messages as $message){
            $seen = $message->getFlags()->get("seen") === "Seen";
            if(!$seen){
                $this->processUnreadMessage($message);
                $nUnread += 1;
            }
            else{
                $this->warn("..skipping email as has been read");
            }
        }
        if($nUnread==0){
            $this->warn("No unread messages found..");
        }
    }

    private function processUnreadMessage($message)
    {
        $this->info("Processing unread message");
        $alias = $this->getAlias($message);
        if(!$alias){
            $this->warn("... skipping, no valid alias");
            return;
        }
        $thread = $this->getThread($alias);
        if(!$thread){
            $this->warn("... skipping, thread cannot be found");
            return;
        }
        $enquiryMessage = $this->scrapeAndStoreContent($message,$thread->id);
        $this->sendEmail($enquiryMessage->id);
        $this->deleteMessage($message);
        
    }

    private function getAlias($message)
    {
        $toaddress = $message->get("toaddress");
        $plusPos = strpos($toaddress, '+');
        $atPos = strpos($toaddress, '@');
        if ($plusPos !== false && $atPos !== false) {
            $alias = substr($toaddress, $plusPos + 1, $atPos - $plusPos - 1);
            $this->info("Found alias ".$alias);
            return $alias;
        }
    }

    private function getThread($alias)
    {         
        $thread = EnquiryThread::where("unique_key",$alias)
                    ->first();

        if ($thread !== null) {
            $this->info("Found existing thread, id=".$thread->id);
        }
        else{
            $this->warn("... could not find a valid thread from the alias");
        }
        return $thread;
    }

    private function scrapeAndStoreContent($message,$threadId)
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
        $this->info("enquiryMessage created!");

        $this->info($enquiryMessage);
        return $enquiryMessage;
    }
    private function deleteMessage($message){
        $message->delete($expunge = true);
        $this->info("--- original messsage deleted");
    }

    private function sendEmail($enquiryMessageId)
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
        foreach ($darManagers as $darManager){
      
            $userEmail = $darManager['user_preferred_email'] === 'secondary' ? $darManager['user_secondary_email'] : $darManager['user_email'];
            $userName = $darManager['user_name'];
      
            $to = [
                'to' => [
                    'email' => $userEmail,
                    'name' => $userName,
                ],
            ];
            $this->info(json_encode($to, JSON_PRETTY_PRINT));

            $replacements = [
                '[[DAR_MANAGER_FIRSTNAME]]' => $darManager['user_firstname'],
                '[[ORIGINAL_MESSAGE_BODY]]' => $enquiryMessage->message_body,
                '[[ORIGINAL_MESSAGE_SENDER]]' => $enquiryMessage->from,
            ];

            $this->info( json_encode($replacements, JSON_PRETTY_PRINT));

            // Note Calum 17/01/2024:
            //    - to be implemented once email design is done 
            // $template = EmailTemplate::where('identifier', '=', '.....')->first();
            // SendEmailJob::dispatch($to, $template, $replacements);
        }
    }

}
