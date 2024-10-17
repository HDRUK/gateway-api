<?php

namespace App\Jobs;

use App\Models\Dataset;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LokiTestJob implements ShouldQueue
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
        $datasets = Dataset::all();
        foreach ($datasets as &$d) {
            if ($d->deleted_at !== null) {
                continue;
            }

            $d->latestVersion();

            unset($d);
        }
    }

    public function tags(): array
    {
        return [
            'testing',
        ];
    }
}
