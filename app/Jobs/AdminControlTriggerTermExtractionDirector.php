<?php

namespace App\Jobs;

use Auditor;
// use App\Http\Traits\LoggingContext;
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
    //use LoggingContext;

    //  private ?array $loggingContext = null;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // $this->loggingContext = $this->getLoggingContext(\request());
        // $this->loggingContext['method_name'] = class_basename($this);
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

        // \Log::info('Manually triggered TED', $this->loggingContext);
    }

    public function tags(): array
    {
        return [
            'admin_ctrl',
            'manual_triggering',
        ];
    }
}
