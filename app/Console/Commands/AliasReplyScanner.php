<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Traits\TeamTransformation;
use AliasReplyScanner as ARS;

class AliasReplyScanner extends Command
{
    use TeamTransformation;

    protected $alias;

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
        $this->info('Found ' . count($messages) . ' new messages');

        foreach ($messages as $i => $message) {
            $this->info('Working on message #' . $i);
            $this->processMessage($message);
        }
    }

    private function processMessage($message)
    {
        $alias = ARS::getAlias($message);

        if ($alias) {
            $this->processAlias($alias, $message);
        } else {
            $this->warn('... alias not found in the email sent');
        }

        ARS::deleteMessage($message);
        $this->info('... message deleted from the inbox');
    }

    private function processAlias($alias, $message)
    {
        $thread = ARS::getThread($alias);

        if ($thread) {
            $this->processThread($message, $thread);
        } else {
            $this->warn('... valid thread not found for key=' . $alias);
        }
    }

    private function processThread($message, $thread)
    {
        $response = ARS::scrapeAndStoreContent($message, $thread->id);
        $this->info('... ' . $response->message_body);
    }
}
