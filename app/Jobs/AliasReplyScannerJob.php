<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\TeamTransformation;
use Log;

use AliasReplyScanner as ARS;

class AliasReplyScannerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TeamTransformation;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messages = ARS::getNewMessagesSafe();
        Log::info('Found ' . count($messages) . ' new messages');

        foreach($messages as $i => $message) {
            Log::info('Working on message #' . $i);
            $this->processMessage($message);
        }
    }

    private function processMessage($message)
    {
        $alias = ARS::getAlias($message);

        if ($alias) {
            $this->processAlias($alias, $message);
        } else {
            Log::warning('... alias not found in the email sent');
        }

        ARS::deleteMessage($message);
        Log::info('... message deleted from the inbox');
    }

    private function processAlias($alias, $message)
    {
        $thread = ARS::getThread($alias);

        if ($thread) {
            $this->processThread($message, $thread);
        } else {
            Log::warning('... valid thread not found for key=' . $alias);
        }
    }

    private function processThread($message, $thread)
    {
        $response = ARS::scrapeAndStoreContent($message, $thread->id);
        Log::info('... ' . $response->message_body);
    }
}
