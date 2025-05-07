<?php

namespace App\Console\Commands;

use App\Models\EnquiryThread;
use App\Models\EnquiryMessage;
use App\Models\EnquiryThreadHasDatasetVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlattenEnquiryThreads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:flatten-enquiry-threads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flatten multi-team enquiry threads into separate threads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('enquiry_threads')->chunkById(100, function ($enquiries) {
            foreach ($enquiries as $enquiry) {
                $enquiryArr = (array) $enquiry;
                $teamIds = json_decode($enquiry->team_ids, true);

                $oneTeam = array_pop($teamIds);
                EnquiryThread::where('id', $enquiry->id)
                    ->update(['team_ids' => [$oneTeam]]);

                $messages = EnquiryMessage::where('thread_id', $enquiry->id)->get();
                $datasets = EnquiryThreadHasDatasetVersion::where('enquiry_thread_id', $enquiry->id)->get();
                foreach ($teamIds as $teamId) {
                    if ($teamId) {
                        $enquiryArr['team_ids'] = [$teamId];
                        $newThread = EnquiryThread::create($enquiryArr);
                        $newThreadId = $newThread->id;

                        foreach ($messages as $message) {
                            EnquiryMessage::create([
                                'thread_id' => $newThreadId,
                                'from' => $message->from,
                                'message_body' => $message->message_body,
                            ]);
                        }

                        foreach ($datasets as $dataset) {
                            EnquiryThreadHasDatasetVersion::create([
                                'enquiry_thread_id' => $newThreadId,
                                'dataset_version_id' => $dataset->dataset_version_id,
                                'interest_type' => $dataset->interest_type,
                            ]);
                        }
                    }
                }
            }
        });
    }
}
