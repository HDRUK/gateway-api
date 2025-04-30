<?php

use App\Models\EnquiryThread;
use App\Models\EnquiryThreadHasDatasetVersion;
use App\Models\EnquiryMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->bigInteger('team_id');
            $table->string('enquiry_unique_key', 32);
        });

        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->json('team_ids')->nullable()->change();
        });

        DB::table('enquiry_threads')->chunkById(100, function ($enquiries) {
            foreach ($enquiries as $enquiry) {
                $enquiryUniqueKey = Str::random(8);
                $enquiryArr = (array) $enquiry;
                $teamIds = json_decode($enquiry->team_ids, true);
                $messages = EnquiryMessage::where('thread_id', $enquiry->id)->get();
                $datasets = EnquiryThreadHasDatasetVersion::where('enquiry_thread_id', $enquiry->id)->get();
                foreach ($teamIds as $teamId) {
                    if ($teamId) {
                        $enquiryArr['team_id'] = $teamId;
                        $enquiryArr['enquiry_unique_key'] = $enquiryUniqueKey;
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
                EnquiryThreadHasDatasetVersion::where('enquiry_thread_id', $enquiry->id)->delete();
                EnquiryMessage::where('thread_id', $enquiry->id)->delete();
                EnquiryThread::where('id', $enquiry->id)->delete();
            }
        });

        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->dropColumn(['team_ids']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->json('team_ids');
        });

        $enquiryKeys = array_unique(
            EnquiryThread::select('unique_key')->pluck('unique_key')->toArray()
        );

        foreach ($enquiryKeys as $key) {
            $threads = EnquiryThread::where('enquiry_unique_key', $key)->get()->toArray();
            $teamIds = array_column($threads, 'team_id');
            $threadIds = array_column($threads, 'id');
            $idToKeep = array_pop($threadIds);

            EnquiryThread::where('id', $idToKeep)
                ->update([
                    'team_ids' => $teamIds
                ]);

            EnquiryThreadHasDatasetVersion::whereIn('enquiry_thread_id', $threadIds)->delete();
            EnquiryMessage::whereIn('thread_id', $threadIds)->delete();
            EnquiryThread::whereIn('id', $threadIds)->delete();
        }

        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->dropColumn(['team_id', 'enquiry_unique_key']);
        });
    }

};
