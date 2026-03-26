<?php

namespace App\Console\Commands;

use App\Models\EnquiryMessage;
use App\Models\EnquiryThread;
use Illuminate\Console\Command;

class AddDateInEnquiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-date-in-enquiry';

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
        $enquiries = EnquiryThread::all();

        foreach ($enquiries as $enquiry) {
            $firstMessage = EnquiryMessage::where('thread_id', $enquiry->id)->orderBy('created_at', 'asc')->first();
            if (!$firstMessage) {
                $this->info($enquiry->id . " - not found");
                continue;
            }

            EnquiryThread::where('id', $enquiry->id)->update([
                'created_at' => $firstMessage->created_at,
                'updated_at' => $firstMessage->created_at,
            ]);

            $this->info($enquiry->id . " - " . $firstMessage->created_at);

        }
    }
}
