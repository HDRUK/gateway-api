<?php

namespace App\Services\Search;

use App\Models\Collection;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Dur;
use App\Models\DurHasPublication;
use App\Models\CollectionHasPublication;
use App\Models\Publication;
use App\Models\PublicationHasTool;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Tool;

class PublicationHydrator
{
    private const LINK_TYPE_MAPPINGS = [
        'USING'   => 'Using a dataset',
        'ABOUT'   => 'About a dataset',
        'UNKNOWN' => 'Unknown',
    ];

    public function hydrate(array $hits): array
    {
        $matchedIds = array_map(fn($h) => (int)$h['_id'], $hits);

        $models = Publication::whereIn('id', $matchedIds)
            ->get()
            ->keyBy('id');

        // Batch-load all relational data in 6 queries instead of 4×N
        [$datasetVersionsByPub, $datasetLinkTypesByPub] = $this->batchDatasetLinks($matchedIds);
        $collectionsByPub = $this->batchCollections($matchedIds);
        $toolsByPub       = $this->batchTools($matchedIds);
        $dursByPub        = $this->batchDurs($matchedIds);

        foreach ($hits as $i => $hit) {
            $model = $models[(int)$hit['_id']] ?? null;
            if (!$model) {
                unset($hits[$i]);
                continue;
            }

            $pubId = $model->id;

            $hits[$i]['_source']['created_at'] = $model->created_at;
            $hits[$i]['_source']['year_of_publication'] = $model->year_of_publication;
            $hits[$i]['paper_title'] = $model->paper_title;
            $hits[$i]['abstract'] = preg_replace('/<h4>(.*?)<\/h4>/', '', $model->abstract);
            $hits[$i]['authors'] = $model->authors;
            $hits[$i]['journal_name'] = $model->journal_name;
            $hits[$i]['year_of_publication'] = $model->year_of_publication;
            $hits[$i]['full_text_url'] = 'https://doi.org/' . $model->paper_doi;
            $hits[$i]['url'] = $model->url;
            $hits[$i]['publication_type'] = $model->publication_type;
            $hits[$i]['datasetLinkTypes'] = $datasetLinkTypesByPub[$pubId] ?? [];
            $hits[$i]['datasetVersions'] = $datasetVersionsByPub[$pubId] ?? [];
            $hits[$i]['collections'] = $collectionsByPub[$pubId] ?? [];
            $hits[$i]['tools'] = $toolsByPub[$pubId] ?? [];
            $hits[$i]['durs'] = $dursByPub[$pubId] ?? [];
        }

        return array_values($hits);
    }

    private function batchDatasetLinks(array $pubIds): array
    {
        $links = PublicationHasDatasetVersion::whereIn('publication_id', $pubIds)
            ->select(['publication_id', 'dataset_version_id', 'link_type'])
            ->get()
            ->groupBy('publication_id');

        $allDvIds = $links->flatten()->pluck('dataset_version_id')->unique()->all();

        if (empty($allDvIds)) {
            return [[], []];
        }

        $dvs = DatasetVersion::whereIn('id', $allDvIds)
            ->select(['id', 'dataset_id'])
            ->get()
            ->keyBy('id');

        $allDatasetIds = $dvs->pluck('dataset_id')->unique()->all();

        $datasets = Dataset::whereIn('id', $allDatasetIds)
            ->where('status', Dataset::STATUS_ACTIVE)
            ->with('latestMetadata')
            ->get()
            ->keyBy('id');

        $versionsByPub  = [];
        $linkTypesByPub = [];

        foreach ($links as $pubId => $pubLinks) {
            foreach ($pubLinks as $link) {
                $dv = $dvs->get($link->dataset_version_id);
                if (!$dv) {
                    continue;
                }
                $dataset = $datasets->get($dv->dataset_id);
                if (!$dataset) {
                    continue;
                }

                $shortTitle = $dataset->latestMetadata?->metadata['metadata']['summary']['shortTitle'] ?? '';
                $versionsByPub[$pubId][] = [
                    'id'         => $dv->id,
                    'dataset_id' => $dv->dataset_id,
                    'name'       => $shortTitle,
                ];
                $linkTypesByPub[$pubId][] = self::LINK_TYPE_MAPPINGS[$link->link_type ?? 'UNKNOWN'];
            }

            if (isset($linkTypesByPub[$pubId])) {
                $linkTypesByPub[$pubId] = array_unique($linkTypesByPub[$pubId]);
            }
        }

        return [$versionsByPub, $linkTypesByPub];
    }

    private function batchCollections(array $pubIds): array
    {
        $links = CollectionHasPublication::whereIn('publication_id', $pubIds)
            ->select(['publication_id', 'collection_id'])
            ->get()
            ->groupBy('publication_id');

        $allIds = $links->flatten()->pluck('collection_id')->unique()->all();
        if (empty($allIds)) {
            return [];
        }

        $models = Collection::whereIn('id', $allIds)
            ->where('status', 'ACTIVE')
            ->select(['id', 'name', 'description'])
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($links as $pubId => $rows) {
            $result[$pubId] = $rows->pluck('collection_id')
                ->map(fn($id) => $models->get($id))
                ->filter()
                ->values();
        }
        return $result;
    }

    private function batchTools(array $pubIds): array
    {
        $links = PublicationHasTool::whereIn('publication_id', $pubIds)
            ->select(['publication_id', 'tool_id'])
            ->get()
            ->groupBy('publication_id');

        $allIds = $links->flatten()->pluck('tool_id')->unique()->all();
        if (empty($allIds)) {
            return [];
        }

        $models = Tool::whereIn('id', $allIds)
            ->where('status', 'ACTIVE')
            ->select(['id', 'name', 'description'])
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($links as $pubId => $rows) {
            $result[$pubId] = $rows->pluck('tool_id')
                ->map(fn($id) => $models->get($id))
                ->filter()
                ->values();
        }
        return $result;
    }

    private function batchDurs(array $pubIds): array
    {
        $links = DurHasPublication::whereIn('publication_id', $pubIds)
            ->select(['publication_id', 'dur_id'])
            ->get()
            ->groupBy('publication_id');

        $allIds = $links->flatten()->pluck('dur_id')->unique()->all();
        if (empty($allIds)) {
            return [];
        }

        $models = Dur::whereIn('id', $allIds)
            ->where('status', 'ACTIVE')
            ->select(['id', 'project_title'])
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($links as $pubId => $rows) {
            $result[$pubId] = $rows->pluck('dur_id')
                ->map(fn($id) => $models->get($id))
                ->filter()
                ->values();
        }
        return $result;
    }
}
