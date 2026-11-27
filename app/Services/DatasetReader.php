<?php

namespace App\Services;

use App\Context\GwdmVersionContext;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\Gwdm\GwdmHandlerFactory;
use App\Services\Gwdm\SourceSchema;
use Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use MetadataManagementController as MMC;
use Swaggest\JsonDiff\JsonPatch;

class DatasetReader
{
    public function __construct(
        private readonly GwdmVersionContext $gwdmVersionContext,
        private readonly GwdmHandlerFactory $handlerFactory,
    ) {
    }

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
     * @param list<int> $datasetIds
     * @return array<int, SourceSchema> the schema each dataset's latest version was submitted in, keyed by dataset id;
     *         datasets with no version are left out
     */
    public function sourceSchemas(array $datasetIds): array
    {
        return Dataset::whereIn('id', $datasetIds)
            ->with(['latestMetadata' => fn ($query) => $query->select([
                'dataset_versions.id',
                'dataset_versions.dataset_id',
                'dataset_versions.source_schema_model',
                'dataset_versions.source_schema_version',
            ])])
            ->get(['id'])
            ->filter(fn (Dataset $dataset) => $dataset->latestMetadata !== null)
            ->mapWithKeys(fn (Dataset $dataset) => [
                $dataset->id => SourceSchema::fromColumns(
                    $dataset->latestMetadata->only(['source_schema_model', 'source_schema_version'])
                ),
            ])
            ->all();
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

    /**
     * The full GWDM metadata of one version: its nearest full copy with every
     * later change up to that version applied.
     *
     * @throws \RuntimeException when the version has no full copy at or before it
     */
    public function gwdm(int $datasetId, int $targetVersion): array
    {
        $snapshot = DatasetVersion::where('dataset_id', $datasetId)
            ->where('version', '<=', $targetVersion)
            ->whereNull('patch')
            ->orderBy('version', 'desc')
            ->first();

        if (! $snapshot) {
            throw new \RuntimeException(
                "No base snapshot found for dataset {$datasetId} at or before version {$targetVersion}."
            );
        }

        if ($snapshot->version === $targetVersion) {
            return $this->storedGwdm($snapshot);
        }

        $deltas = DatasetVersion::where('dataset_id', $datasetId)
            ->where('version', '>', $snapshot->version)
            ->where('version', '<=', $targetVersion)
            ->whereNotNull('patch')
            ->orderBy('version')
            ->get(['version', 'patch']);

        return $this->applyChanges($this->storedGwdm($snapshot), $deltas);
    }

    /**
     * The metadata envelope ({gwdmVersion, metadata, original_metadata}) of one version.
     *
     * @return array{gwdmVersion: string, metadata: array, original_metadata: array}
     */
    public function envelope(
        int $datasetId,
        int $targetVersion,
        bool $validate = true,
        ?DatasetVersion $prefetched = null,
        bool $applySupplementary = true,
    ): array {
        $row = $prefetched ?? DatasetVersion::where('dataset_id', $datasetId)
            ->where('version', $targetVersion)
            ->first();

        $gwdm = $prefetched !== null && $prefetched->patch === null
            ? $this->storedGwdm($prefetched)
            : $this->gwdm($datasetId, $targetVersion);

        $envelope = $this->toEnvelope($row, $gwdm, $applySupplementary);

        if ($validate) {
            $this->validate($envelope, $datasetId, $targetVersion);
        }

        return $envelope;
    }

    /**
     * The envelope of each dataset's latest version as it was stored, rebuilt in a
     * fixed number of queries however many datasets are asked for. Linkages are not
     * merged in from the linkage tables, and nothing is validated.
     *
     * @param list<int> $datasetIds
     * @return array<int, array{gwdmVersion: string, metadata: array, original_metadata: array}> keyed by dataset id;
     *         datasets with no version, or whose changes have no full copy to start from, are left out
     */
    public function latestEnvelopes(array $datasetIds): array
    {
        $latest = $this->latestVersionRows($datasetIds);
        $changedIds = $latest->whereNotNull('patch')->keys()->all();
        $snapshots = $changedIds === [] ? new EloquentCollection() : $this->nearestSnapshots($changedIds);
        $deltas = $changedIds === [] ? collect() : $this->deltasAfterLatestSnapshot($changedIds)->groupBy('dataset_id');

        $envelopes = [];
        foreach ($latest as $datasetId => $row) {
            if ($row->patch === null) {
                $envelopes[$datasetId] = $this->toEnvelope($row, $this->storedGwdm($row), false);
                continue;
            }

            if (! $snapshots->has($datasetId)) {
                \Log::warning('Dataset has changes but no full copy to rebuild from', ['dataset_id' => $datasetId]);
                continue;
            }

            $gwdm = $this->applyChanges(
                $this->storedGwdm($snapshots[$datasetId]),
                ($deltas[$datasetId] ?? collect())->sortBy('version'),
            );
            $envelopes[$datasetId] = $this->toEnvelope($row, $gwdm, false);
        }

        return $envelopes;
    }

    /**
     * Replaces the stored metadata of each dataset's loaded `latestMetadata` with
     * its rebuilt metadata, for responses that serialise that version row. Versions
     * stored in full are left untouched.
     *
     * @param iterable<Dataset> $datasets
     */
    public function fillLatestMetadata(iterable $datasets): void
    {
        $changed = [];
        foreach ($datasets as $dataset) {
            if ($dataset->relationLoaded('latestMetadata') && $dataset->latestMetadata?->patch !== null) {
                $changed[$dataset->id] = $dataset;
            }
        }
        $envelopes = $this->latestEnvelopes(array_keys($changed));

        foreach ($changed as $dataset) {
            if (isset($envelopes[$dataset->id])) {
                $dataset->latestMetadata->metadata = $envelopes[$dataset->id];
            }
        }
    }

    /**
     * @param list<int> $datasetIds
     * @return Collection<int, DatasetVersion> keyed by dataset id
     */
    private function latestVersionRows(array $datasetIds): Collection
    {
        return Dataset::whereIn('id', $datasetIds)
            ->with('latestMetadata')
            ->get(['id'])
            ->pluck('latestMetadata')
            ->filter()
            ->keyBy('dataset_id');
    }

    /**
     * @param list<int> $datasetIds
     * @return EloquentCollection<int, DatasetVersion> each dataset's latest full copy, keyed by dataset id
     */
    private function nearestSnapshots(array $datasetIds): EloquentCollection
    {
        return DatasetVersion::query()
            ->joinSub($this->latestSnapshotVersions($datasetIds), 'snapshot', fn (JoinClause $join) => $join
                ->on('dataset_versions.dataset_id', '=', 'snapshot.dataset_id')
                ->on('dataset_versions.version', '=', 'snapshot.version'))
            ->select('dataset_versions.*')
            ->get()
            ->keyBy('dataset_id');
    }

    /**
     * @param list<int> $datasetIds
     * @return EloquentCollection<int, DatasetVersion> the changes stored after each dataset's latest full copy
     */
    private function deltasAfterLatestSnapshot(array $datasetIds): EloquentCollection
    {
        return DatasetVersion::query()
            ->joinSub($this->latestSnapshotVersions($datasetIds), 'snapshot', fn (JoinClause $join) => $join
                ->on('dataset_versions.dataset_id', '=', 'snapshot.dataset_id')
                ->on('dataset_versions.version', '>', 'snapshot.version'))
            ->whereNotNull('dataset_versions.patch')
            ->get(['dataset_versions.dataset_id', 'dataset_versions.version', 'dataset_versions.patch']);
    }

    /**
     * @param list<int> $datasetIds
     * @return Builder<DatasetVersion>
     */
    private function latestSnapshotVersions(array $datasetIds): Builder
    {
        return DatasetVersion::query()
            ->whereIn('dataset_id', $datasetIds)
            ->whereNull('patch')
            ->groupBy('dataset_id')
            ->select('dataset_id')
            ->selectRaw('MAX(version) AS version');
    }

    private function storedGwdm(DatasetVersion $snapshot): array
    {
        $stored = (array) $snapshot->metadata;
        $gwdmVersion = $snapshot->gwdm_version
            ?? $stored['gwdmVersion']
            ?? $this->gwdmVersionContext->targetVersion();

        return $this->handlerFactory->resolve($gwdmVersion)->extractStoredGwdm($stored);
    }

    /**
     * @param Collection<int, DatasetVersion> $deltas in version order
     */
    private function applyChanges(array $gwdm, Collection $deltas): array
    {
        // swaggest/json-diff works on stdClass graphs and applies patches in place.
        $current = json_decode(json_encode($gwdm));

        foreach ($deltas as $delta) {
            JsonPatch::import(json_decode(json_encode($delta->patch)))->apply($current);
        }

        return json_decode(json_encode($current), true);
    }

    /**
     * @return array{gwdmVersion: string, metadata: array, original_metadata: array}
     */
    private function toEnvelope(DatasetVersion $row, array $gwdm, bool $applySupplementary): array
    {
        $gwdmVersion = $row->gwdm_version
            ?? $row->metadata['gwdmVersion']
            ?? $this->gwdmVersionContext->targetVersion();

        // Supplementary data comes from the linkage and section tables. Writers of
        // those tables skip it so they don't read back the rows they are rewriting.
        if ($applySupplementary) {
            $handler = $this->handlerFactory->resolve($gwdmVersion);
            $handler->preloadSections($row);
            $gwdm = array_merge($gwdm, $handler->afterRead($row));
        }

        return [
            'gwdmVersion' => $gwdmVersion,
            'metadata' => $gwdm,
            'original_metadata' => $row->metadata['original_metadata'] ?? [],
        ];
    }

    /**
     * Only for callers that ask: data is validated when written, and validating on
     * every read would make TRASER a hard dependency of reading.
     */
    private function validate(array $envelope, int $datasetId, int $targetVersion): void
    {
        $metadataJson = json_encode(['metadata' => $envelope['metadata']]);
        $isValid = MMC::validateDataModelType($metadataJson, Config::get('metadata.GWDM.name'), $envelope['gwdmVersion']);

        if (! $isValid) {
            \Log::warning('GWDM read validation failed', [
                'dataset_id' => $datasetId,
                'version' => $targetVersion,
                'gwdm_version' => $envelope['gwdmVersion'],
            ]);
            throw new \RuntimeException("Reconstructed GWDM for version {$envelope['gwdmVersion']} failed schema validation.");
        }
    }
}
