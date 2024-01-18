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
        $messages = ARS::getNewMessages();
        foreach($messages as $message){
            $alias = ARS::getAlias($message);
            if(!$alias){
                continue;
            }
            $thread = ARS::getThread($alias);
            if(!$thread){
                $this->error("Cannot find associated thread. Alias ".$alias." is not valid");
                continue;
            }
            $response = ARS::scrapeAndStoreContent($message,$thread->id);
            $this->info($response);

            $response = ARS::sendEmail($response->id);
            //$this->info($response);
            $this->info(count($response)." emails sent");
            //$response = ESS::deleteMessage($message);
            //$this->info($response);
        }
    }


}