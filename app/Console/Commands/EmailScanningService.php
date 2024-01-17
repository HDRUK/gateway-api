<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\EnquiryMessages;
use App\Models\EnquiryThread;

use App\Models\Team;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;

use Webklex\PHPIMAP\ClientManager;


class EmailScanningService extends Command
{
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
            'host'          => env('IMAP_HOST'),
            'port'          => env('IMAP_PORT'),
            'encryption'    => env('IMAP_ENCRYPTION'),
            'validate_cert' => env('IMAP_VALIDATE_CERT'),
            'username'      => env('IMAP_USERNAME'),
            'password'      => env('IMAP_PASSWORD'),
            'protocol'      => env('IMAP_PROTOCOL'),
        ]);

        $client->connect();
        $inbox = $client->getFolder(env('IMAP_INBOX_NAME'));
        $messages = $inbox->messages()->all()->get();
        foreach($messages as $message){
            $seen = $message->getFlags()->get("seen") === "Seen";
            if(!$seen){
                $this->processUnreadMessage($message);
            }
        }   
    }

    private function hashId(int $id, string $name)
    {
        return base64_encode($name.":".$id);
    }

    private function decodeHashId(string $encodedId, string $name)
    {
        return str_replace($name.":", '', base64_decode($encodedId));
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
            if(strpos($alias, '_') !== false){
                $this->info("Found alias ".$alias);
                return $alias;
            }
        }
    }

    private function getThread($alias)
    {
        list($part1,$part2) = explode("_",$alias);
        $enquiryId = $this->decodeHashId($part1,"enquiry_id");
        $userId = $this->decodeHashId($part2,"user_id");   
        $userId = '2977';            
        $thread = EnquiryThread::where("id",$enquiryId)
                    ->where("user_id",$userId)
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

        $this->info($enquiryMessage);
        return $enquiryMessage;
    }
    private function deleteMessage($message){
        //$message->delete($expunge = true);
        $this->info("--- original messsage deleted");
    }

    private function sendEmail($enquiryMessageId)
    {
        $enquiryMessage = EnquiryMessages::with("thread")
                            ->where("id",$enquiryMessageId)
                            ->first();
        
        $teamId = $enquiryMessage->thread->team_id;
        $team = Team::with("roles")
                ->where("id",$teamId)
                ->first();

        $managers = $team->roles
                    ->where("name","dar.manager")
                    ->where("enabled",true);

        $this->info($managers);

        
        /*
        $to = [
            'to' => [
                'email' => $userEmail,
                'name' => $user['name'],
            ],
        ];

        $replacements = [
            '[[USER_FIRSTNAME]]' => $user['firstname'],
            '[[ORIGINAL_MESSAGE_BODY]]' => $enquiryMessage->body,
            '[[ORIGINAL_MESSAGE_SENDER]]' => $enquiryMessage->from,
        ];

        SendEmailJob::dispatch($to, $template, $replacements);
        */
    }

}
