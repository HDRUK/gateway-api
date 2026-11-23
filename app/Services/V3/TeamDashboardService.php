<?php

namespace App\Services\V3;

use App\Enums\SortDirection;
use App\Models\Dataset;
use App\Services\BigQueryService;
use App\Services\V3\Dashboard\BigQueryQuery;
use App\Services\V3\Dashboard\DashboardQueries;
use Carbon\CarbonImmutable;

class TeamDashboardService
{
    private readonly string $viewsTable;

    public function __construct(
        private readonly BigQueryService $bigQueryService,
    ) {
        $this->viewsTable = config('services.googlebigquery.project_id') . '.' . config('services.googlebigquery.dashboard_dataset') . '.' . config('services.googlebigquery.dashboard_table');
    }

    public function getCount(string $model, string $dateColumn, int $teamId, $startDate, $endDate, array $extraColumn)
    {
        $base = $model::where('team_id', $teamId);

        if (count($extraColumn)) {
            $base->where($extraColumn);
        }

        $total = (clone $base)->count();

        $intervalQuery = clone $base;

        if ($startDate && $endDate) {
            $intervalQuery->whereBetween($dateColumn, [$startDate, $endDate . ' 23:59:59']);
        } else {
            $intervalQuery->where($dateColumn, '>=', now()->subMonths(12));
        }

        return [
            'total'             => $total,
            'total_by_interval' => $intervalQuery->count(),
        ];
    }

    public function getDatasetViews(int $teamId, string $startDate, string $endDate): array
    {
        return $this->run(DashboardQueries::datasetViewsTimeline(
            $this->viewsTable,
            $teamId,
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
        ));
    }

    public function getDatatasetViewsTop(int $teamId, string $startDate, string $endDate): array
    {
        return $this->getDatasetViewsRanked($teamId, $startDate, $endDate, SortDirection::DESC);
    }

    public function getDatasetViewsBottom(int $teamId, string $startDate, string $endDate): array
    {
        return $this->getDatasetViewsRanked($teamId, $startDate, $endDate, SortDirection::ASC);
    }

    private function getDatasetViewsRanked(int $teamId, string $startDate, string $endDate, SortDirection $direction): array
    {
        $responseBq = $this->run(DashboardQueries::datasetViewsRanked(
            $this->viewsTable,
            $teamId,
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
            $direction,
        ));

        $response = [];

        foreach ($responseBq as $row) {
            $dataset = Dataset::where('id', $row['entity_id'])->first();

            if (!$dataset) {
                continue;
            }

            $shortTitle = $dataset->latestVersion(['short_title'])->short_title ?? null;

            if ($shortTitle) {
                $response[] = [
                    'id' => $row['entity_id'],
                    'title' => $shortTitle,
                    'counter' => $row['counter'],
                ];
            }
        }

        return $response;
    }

    public function getEntityViews(string $entity, int $teamId, string $startDate, string $endDate): array
    {
        return $this->run(DashboardQueries::entityViews(
            $this->viewsTable,
            $entity,
            $teamId,
            CarbonImmutable::parse($startDate),
            CarbonImmutable::parse($endDate),
        ));
    }

    private function run(BigQueryQuery $query): array
    {
        return $this->bigQueryService->query($query->sql, $query->params);
    }
}
