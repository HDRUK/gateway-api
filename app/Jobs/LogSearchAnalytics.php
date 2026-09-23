<?php

namespace App\Jobs;

use App\Services\BigQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Horizon\Contracts\Silenced;

class LogSearchAnalytics implements ShouldQueue, Silenced
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $timeout = 30;

    public function __construct(private readonly SearchAnalyticsData $data)
    {
    }

    public function handle(BigQueryService $bigQuery): void
    {
        try {
            $bigQuery->insertRow(
                config('services.googlebigquery.search_dataset'),
                config('services.googlebigquery.search_analytics_table'),
                $this->buildRow(),
                $this->data->uuid
            );
        } catch (\Throwable $e) {
            \Log::error('Failed to log search analytics', ['error' => $e->getMessage()]);
        }
    }

    private function buildRow(): array
    {
        return [
            'UUID' => $this->data->uuid,
            'Timestamp' => now()->format('Y-m-d H:i:s'),
            'EntityType' => $this->data->entityType,
            'SearchTerm' => $this->data->searchTerm ?? '',
            'FilterUsed' => $this->buildFilterUsed(),
            'PageResults' => json_encode(['entity_ids' => $this->data->entityIds]),
            'EntitiesReturned' => $this->data->entitiesReturned,
        ];
    }

    public function buildFilterUsed(): string
    {
        return json_encode([
            'filters' => $this->data->filters,
            'dataSource' => $this->data->dataSource,
        ]);
    }

    public function tags(): array
    {
        return ['search_analytics'];
    }
}
