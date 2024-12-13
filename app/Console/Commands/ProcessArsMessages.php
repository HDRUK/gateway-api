<?php

namespace App\Console\Commands;

use App\Services\ImapService;
use Illuminate\Console\Command;

class ProcessArsMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-ars-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process ARS/IMAP Messages';

    /**
     * The ImapService instance.
     *
     * @var ImapService
     */
    protected $imapService;

    /**
     * Create a new command instance.
     *
     * @param ImapService $imapService
     * @return void
     */
    public function __construct(ImapService $imapService)
    {
        parent::__construct();
        $this->imapService = $imapService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folder = config('mail.mailers.ars.inbox');
        $this->info("Fetching messages from folder: $folder ...");

        try {
            $messages = $this->imapService->getMessagesFromFolder($folder);
        } catch (\Exception $e) {
            $this->error("Failed to connect or retrieve messages: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $count = 0;
        foreach ($messages as $msg) {
            // getPriority()
            // getSubject()
            // getMessageId()
            // getMessageNo()
            // getReferences()
            // getDate()
            // getFrom()
            // getTo()
            // getCc()
            // getBcc()
            // getReplyTo()
            // getInReplyTo()
            // getSender()

            $replyText = preg_replace('/[\r\n]+_{2,}[\s\S]+/m', '', $msg->getTextBody());
            $replyText = preg_replace('/From:.*$/m', '', $replyText);
            $subject = $msg->getSubject()[0];
            // $date = $msg->getDate()[0]->toDateTimeString();
            dd([
                // $msg->getPriority(),
                // $msg->getSubject(),
                $subject,
                // $msg->getMessageId(),
                // $msg->getMessageNo(),
                // $msg->getReferences(),
                // $msg->getDate(),
                // $msg->getFrom(),
                // $msg->getFrom()[0],
                // $msg->getTo(),
                // $msg->getCc(),
                // $msg->getBcc(),
                // $msg->getReplyTo(),
                // $msg->getInReplyTo(),
                // $msg->getSender(),
                // $msg->getTextBody(),
                // $msg->parseBody(),
                trim(strip_tags($replyText)),
            ]);

            $count++;
        }

        $this->info("$count message(s) processed and deleted.");
        return Command::SUCCESS;
    }
}
