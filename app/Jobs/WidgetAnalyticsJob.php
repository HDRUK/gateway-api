<?php

namespace App\Jobs;

use App\Models\WidgetAnalytic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Horizon\Contracts\Silenced;

class WidgetAnalyticsJob implements ShouldQueue, Silenced
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        WidgetAnalytic::create($this->data);
    }

    public function tags(): array
    {
        return ['widget_analytics'];
    }
}
