<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Traits\TeamTransformation;

use AliasReplyScanner AS ARS;


class AliasReplyScanner extends Command
{
    use TeamTransformation;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:alias-reply-scanner';

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
        $messages = ARS::getNewMessagesSafe();
        $this->info("Found ".count($messages)." new messages");

        foreach($messages as $i => $message){
            $this->info("Working on message #".$i);
            $alias = ARS::getAlias($message);
            if($alias){   
                $thread = ARS::getThread($alias);
                if($thread){
                    $response = ARS::scrapeAndStoreContent($message,$thread->id);
                    $this->info("... ".$response->message_body);
                    $response = ARS::sendEmail($response->id);
                    $nEmailsSent = count($response);
                    $msg =  "... ".$nEmailsSent." emails sent";
                    if($nEmailsSent>0){
                        $this->info($msg);
                    }
                    else{
                        $this->warn($msg);
                    } 
                   
                }else{
                    $this->warn("... valid thread not found for key=".$alias);
                }
            }else{
                $this->warn("... alias not found in the email sent");
            }
            //$response = ESS::deleteMessage($message);
            //$this->info($response);
        }
    }


}