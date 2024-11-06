<?php

namespace App\Jobs;

use CloudLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\TeamTransformation;

use AliasReplyScanner as ARS;

class AliasReplyScannerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TeamTransformation;

    private int $noMessagesFound = 0;

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
        CloudLogger::write('Found ' . count($messages) . ' new messages');

        $this->noMessagesFound = count($messages);

        foreach($messages as $i => $message) {
            CloudLogger::write('Working on message #' . $i);
            $this->processMessage($message);
        }
    }

    public function processMessage($message)
    {
        $alias = ARS::getAlias($message);

        if ($alias) {
            $this->processAlias($alias, $message);
        } else {
            CloudLogger::write('... alias not found in the email sent');
        }

        ARS::deleteMessage($message);
        CloudLogger::write('... message deleted from the inbox');
    }

    public function processAlias($alias, $message)
    {
        $thread = ARS::getThread($alias);

        if ($thread) {
            $this->processThread($message, $thread);
        } else {
            CloudLogger::write('... valid thread not found for key=' . $alias);
        }
    }

    public function processThread($message, $thread)
    {
        $response = ARS::scrapeAndStoreContent($message, $thread->id);
        CloudLogger::write('... ' . json_encode($response->message_body));
    }

    public function tags(): array
    {
        return [
            'alias_reply_scanner',
            'no_of_messages_found:' . $this->noMessagesFound,
        ];
    }
}
