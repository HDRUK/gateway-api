<?php

namespace App\Jobs;

use Auditor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class AdminControlTriggerTermExtractionDirector implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        Artisan::call('app:reindex-entities datasets --term-extraction --minIndex=800 --maxIndex=802');

        Auditor::log([
            'action_type' => 'ADMIN_CTRL',
            'action_name' => class_basename($this) . '@'.__FUNCTION__,
            'description' => 'manually triggered TED',
        ]);
    }

    public function tags(): array
    {
        return [
            'admin_ctrl',
            'manual_triggering',
        ];
    }
}
