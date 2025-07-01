<?php

namespace App\Console\Commands;

use App\Models\EnquiryThread;
use DB;
use App\Models\User;
use Illuminate\Console\Command;

class UpdatePreferredEmailEnquiryTreadGat7097 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-preferred-email-enquiry-tread-gat7097';

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
        $enquiryThreads = DB::select('SELECT user_id FROM enquiry_threads GROUP BY user_id');

        foreach ($enquiryThreads as $enquiryThread) {
            $userId = $enquiryThread->user_id;

            $userPreferredEmail = User::where([
                'id' => $userId,
            ])->select('preferred_email')->first();

            $this->info('user_id: ' . $userId . ' preferred_email: ' . $userPreferredEmail->preferred_email);

            EnquiryThread::where([
                'user_id' => $userId,
            ])->update([
                'user_preferred_email' => $userPreferredEmail->preferred_email
            ]);
        }
    }
}
