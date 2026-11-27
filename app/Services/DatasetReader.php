<?php

namespace App\Services;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DatasetReader
{
    /**
     * @param list<int> $datasetIds
     * @return array<int, DatasetTitles> keyed by dataset id; datasets with no version are left out
     */
    public function titles(array $datasetIds): array
    {
        return Dataset::whereIn('id', $datasetIds)
            ->with(['latestMetadata' => fn ($query) => $query->select([
                'dataset_versions.id',
                'dataset_versions.dataset_id',
                'dataset_versions.title',
                'dataset_versions.short_title',
            ])])
            ->get(['id'])
            ->filter(fn (Dataset $dataset) => $dataset->latestMetadata !== null)
            ->mapWithKeys(fn (Dataset $dataset) => [
                $dataset->id => new DatasetTitles($dataset->latestMetadata->title, $dataset->latestMetadata->short_title),
            ])
            ->all();
    }

    public function titlesOf(int $datasetId): DatasetTitles
    {
        return $this->titles([$datasetId])[$datasetId]
            ?? throw (new ModelNotFoundException())->setModel(Dataset::class, [$datasetId]);
    }

    /**
     * @param list<string> $pids
     * @return array<string, ?string> one entry per pid, in the order given; null when no dataset or title is found
     */
    public function titlesForPids(array $pids): array
    {
        $datasetIdByPid = Dataset::whereIn('pid', $pids)->pluck('id', 'pid');
        $titles = $this->titles($datasetIdByPid->values()->all());

        return collect($pids)->mapWithKeys(function (string $pid) use ($datasetIdByPid, $titles) {
            $datasetId = $datasetIdByPid->get($pid);
            $title = $datasetId === null ? null : ($titles[$datasetId] ?? null)?->title;

            return [$pid => filled($title) ? $title : null];
        })->all();
    }

    /**
     * @return Builder<DatasetVersion>
     */
    public static function latestTitleOfEachDataset(): Builder
    {
        return DatasetVersion::query()
            ->select('title')
            ->whereColumn('dataset_versions.dataset_id', 'datasets.id')
            ->latest('version')
            ->limit(1);
    }
}
