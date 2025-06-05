<?php

namespace App\Console\Commands;

use App\Models\EnquiryThread;
use App\Models\EnquiryMessage;
use Illuminate\Console\Command;
use App\Models\EnquiryThreadHasDatasetVersion;

class UpdateEnquiryThreadsGat7178 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-enquiry-threads-gat7178';

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
        $array = [
            [
                "id" => 141,
                "user_id"  => 1112,
                "team_ids"  => [21, 119],
                "project_title"  => "",
                "unique_key"  => "dVdRE8gI",
                "is_dar_dialogue"  => 0,
                "is_dar_status"  => 0,
                "enabled"  => 1,
                "is_general_enquiry"  => 1,
                "is_feasibility_enquiry"  => 0,
                "is_dar_review"  => 0,
                "created_at"  => null,
                "updated_at"  => null
            ],
            [
                "id"  => 142,
                "user_id"  => 1112,
                "team_ids"  => [21, 119],
                "project_title"  => "",
                "unique_key"  => "YWxdOyyr",
                "is_dar_dialogue"  => 0,
                "is_dar_status"  => 0,
                "enabled"  => 1,
                "is_general_enquiry"  => 1,
                "is_feasibility_enquiry"  => 0,
                "is_dar_review"  => 0,
                "created_at"  => null,
                "updated_at"  => null
            ],
            [
                "id"  => 148,
                "user_id"  => 4608,
                "team_ids"  => [48, 14, 45, 19],
                "project_title"  => "Accelerating AI-Driven Clinical Tools Using Real-World Health Data from Acute and Chronic Care Settings",
                "unique_key"  => "iBWfwKX2",
                "is_dar_dialogue"  => 0,
                "is_dar_status"  => 0,
                "enabled"  => 1,
                "is_general_enquiry"  => 0,
                "is_feasibility_enquiry"  => 1,
                "is_dar_review"  => 0,
                "created_at"  => null,
                "updated_at"  => null
            ],
        ];

        foreach ($array as $value) {
            $this->info("Processing enquiry thread with ID: {$value['id']}");

            $id = $value['id'];
            $teamIds = $value['team_ids'];

            foreach ($teamIds as $teamId) {
                $this->info("Processing team ID: {$teamId}");

                $check = EnquiryThread::where([
                        'id' => $id,
                        'team_id' => $teamId,
                    ])
                    ->first();

                if (!is_null($check)) {
                    $this->warn("Enquiry thread with ID: {$id} and team ID: {$teamId} already exists. Skipping.");
                    continue;
                }

                $newEnquiryThread = EnquiryThread::create([
                    'id' => $id,
                    'user_id' => $value['user_id'],
                    'team_id' => $teamId,
                    'project_title' => $value['project_title'],
                    'unique_key' => $value['unique_key'],
                    'enquiry_unique_key' => $value['unique_key'],
                    'is_dar_dialogue' => $value['is_dar_dialogue'],
                    'is_dar_status' => $value['is_dar_status'],
                    'enabled' => $value['enabled'],
                    'is_general_enquiry' => $value['is_general_enquiry'],
                    'is_feasibility_enquiry' => $value['is_feasibility_enquiry'],
                    'is_dar_review' => $value['is_dar_review'],
                ]);
                $this->info("Created new enquiry thread with ID: {$newEnquiryThread->id} for team ID: {$teamId} and for original ID: {$id}");

                $messages = EnquiryMessage::where('thread_id', $newEnquiryThread->id)->get();
                $datasets = EnquiryThreadHasDatasetVersion::where('enquiry_thread_id', $newEnquiryThread->id)->get();

                foreach ($messages as $message) {
                    EnquiryMessage::create([
                        'thread_id' => $newEnquiryThread->id,
                        'from' => $message->from,
                        'message_body' => $message->message_body,
                    ]);
                    $this->info("Copied message from thread ID: {$message->thread_id} to new thread ID: {$newEnquiryThread->id}");
                }

                foreach ($datasets as $dataset) {
                    EnquiryThreadHasDatasetVersion::create([
                        'enquiry_thread_id' => $newEnquiryThread->id,
                        'dataset_version_id' => $dataset->dataset_version_id,
                        'interest_type' => $dataset->interest_type,
                    ]);
                    $this->info("Copied dataset version ID: {$dataset->dataset_version_id} to new thread ID: {$newEnquiryThread->id}");
                }
            }
        }

        $this->info('Enquiry threads updated successfully.');
        return 0;
    }
}
