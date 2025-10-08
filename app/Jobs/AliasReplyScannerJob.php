<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\LoggingContext;
use App\Http\Traits\TeamTransformation;
use AliasReplyScanner as ARS;

class AliasReplyScannerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TeamTransformation;
    use LoggingContext;

    private int $noMessagesFound = 0;
    private ?array $loggingContext = null;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->loggingContext = $this->getLoggingContext(\request());
        $this->loggingContext['method_name'] = class_basename($this);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messages = ARS::getNewMessagesSafe();
        \Log::info('Found ' . count($messages) . ' new messages', $this->loggingContext);

        $this->noMessagesFound = count($messages);

        foreach ($messages as $i => $message) {
            \Log::info('Working on message #' . $i, $this->loggingContext);
            $this->processMessage($message);
        }
    }

    public function processMessage($message)
    {
        $alias = ARS::getAlias($message);

        if ($alias) {
            $this->processAlias($alias, $message);
        } else {
            \Log::info('... alias not found in the email sent', $this->loggingContext);
        }

        ARS::deleteMessage($message);
        \Log::info('... message deleted from the inbox', $this->loggingContext);
    }

    public function processAlias($alias, $message)
    {
        $thread = ARS::getThread($alias);

        if ($thread) {
            $this->processThread($message, $thread);
        } else {
            \Log::info('... valid thread not found for key=' . $alias, $this->loggingContext);
        }
    }

    public function processThread($message, $thread)
    {
        $response = ARS::scrapeAndStoreContent($message, $thread->id);
        \Log::info('... ' . json_encode($response->message_body), $this->loggingContext);
    }

    public function tags(): array
    {
        return [
            'alias_reply_scanner',
            'no_of_messages_found:' . $this->noMessagesFound, //Bug: this value doesn't get updated before the tag is published, so it's always 0
        ];
    }
}
