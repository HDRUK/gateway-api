<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\EnquiryMessages;
use App\Models\EnquiryThread;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Client;

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
            ///

            //echo 'Attachments: '.$message->getAttachments()->count().'<br />';
            //echo $message->getHTMLBody();

            //Move the current Message to 'INBOX.read'
            /*if($message->move('INBOX.read') == true){
                echo 'Message has ben moved';
            }else{
                echo 'Message could not be moved';
            }*/
        }   
    }
    private function processUnreadMessage($message)
    {
        // echo $message->getHTMLBody() . "\n";
        $this->hasValidAlias($message);
    }

    private function hasValidAlias($message)
    {
        $email = $message->getFrom();

    }

    private function scrapeAndStoreContent($message)
    {
        echo $message->getHTMLBody() . "\n";

    }
}
