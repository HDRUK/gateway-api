<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;

/**
 * Per-version metadata lifecycle handler.
 *
 * Each concrete subclass encapsulates everything that differs between GWDM
 * schema versions: the required block shape, the publisher field format, and
 * any structured-table persistence (GWDM 3.0+).
 *
 * DatasetService delegates all version-specific mutations to the handler
 * returned by GwdmHandlerFactory::resolve(). There should be no version_compare
 * calls outside of GwdmHandlerFactory.
 *
 * Hierarchy:
 *   GwdmMetadataHandler (abstract — shared logic)
 *   ├── Gwdm1xHandler    (GWDM < 1.1 — legacy publisher field)
 *   ├── Gwdm2xHandler    (GWDM >= 1.1 — covers 2.0 and 2.1)
 *   └── Gwdm30Handler    (GWDM 3.0 — extends 2.x, adds SQL table hooks)
 */
abstract class GwdmMetadataHandler
{
    public function __construct(protected readonly string $resolvedVersion)
    {
    }

    // ── Version identity ──────────────────────────────────────────────────────

    /**
     * Return the exact GWDM version string this instance was resolved for.
     * Used when writing gwdmVersion into the metadata envelope.
     */
    public function version(): string
    {
        return $this->resolvedVersion;
    }

    // ── Metadata preparation (version-specific) ───────────────────────────────

    /**
     * Apply all version-specific mutations to the post-TRASER GWDM array.
     *
     * Concrete implementations must:
     *   1. Call buildRequiredBlock() and write the result into $gwdm['required'],
     *      merging over any existing required fields so DB-derived values win.
     *   2. Call buildPublisher() and write the result into $gwdm['summary']['publisher'].
     *
     * @param  array   $gwdm          Post-TRASER GWDM metadata object
     * @param  Dataset $dataset       The parent dataset row (for gatewayId, pid, dates)
     * @param  Team    $team          Owning team (for publisher fields)
     * @param  int     $versionNumber The version number being written (1 on create)
     * @return array                  Mutated GWDM array ready for envelope + storage
     */
    abstract public function prepareMetadata(
        array $gwdm,
        Dataset $dataset,
        Team $team,
        int $versionNumber,
    ): array;

    // ── Required block ────────────────────────────────────────────────────────

    /**
     * Build the metadata.required section for this schema version.
     * Gwdm1xHandler omits the 'version' field; Gwdm2xHandler+ includes it.
     */
    abstract public function buildRequiredBlock(Dataset $dataset, int $versionNumber): array;

    // ── Publisher field ───────────────────────────────────────────────────────

    /**
     * Build the summary.publisher object for this schema version.
     *
     * Takes the publisher from the incoming metadata payload ($incoming) when its
     * gateway identifier references an existing team, and otherwise falls back to
     * attributing the metadata to the requesting $team.
     *
     * KNOWN ISSUE: the publisher gateway identifier is a raw team primary key
     * (e.g. "07"), not the team's persistent id (pid) — inconsistent with the
     * rest of the model, and there is no check that the requesting team is
     * authorised to publish as the named team. Properly resolved/normalised on
     * fix/GAT-9018-publisher-fix; tracked for a separate design/investigation.
     */
    abstract public function buildPublisher(Team $team, array $incoming = []): array;

    // ── Storage envelope ──────────────────────────────────────────────────────

    /**
     * Wrap the prepared GWDM array and original metadata into the JSON envelope
     * stored in the dataset_versions.metadata column.
     *
     * Shape: { gwdmVersion, metadata: {...GWDM...}, original_metadata: {...} }
     *
     * Shared across all JSON-based versions (2.x and below). Gwdm30Handler
     * may override this when structured SQL columns replace JSON storage.
     */
    public function buildEnvelope(array $gwdm, array $originalMetadata): array
    {
        return [
            'gwdmVersion'       => $this->version(),
            'metadata'          => $gwdm,
            'original_metadata' => $originalMetadata,
        ];
    }

    /**
     * Extract the GWDM content from the raw stored envelope array.
     *
     * Default: handles the modern 2.x envelope {"metadata": {...}, ...} and
     * the legacy format where raw GWDM was stored directly with no wrapper.
     * Gwdm30Handler overrides this to return [] because all GWDM content
     * is in SQL tables and populated by afterRead().
     */
    public function extractStoredGwdm(array $storedData): array
    {
        if (array_key_exists('metadata', $storedData)) {
            return $storedData['metadata'];
        }
        return $storedData;
    }

    // ── Title extraction ──────────────────────────────────────────────────────

    /**
     * Extract the title and shortTitle from the GWDM summary section.
     * These populate the indexed title/short_title columns on dataset_versions.
     *
     * @return array{0: string|null, 1: string|null}
     */
    public function extractTitleFields(array $gwdm): array
    {
        $title      = $gwdm['summary']['title'] ?? null;
        $shortTitle = $gwdm['summary']['shortTitle'] ?? $title;

        return [$title, $shortTitle];
    }

    // ── Persistence hooks (overridden by Gwdm30Handler) ───────────────────────

    /**
     * Called after the DatasetVersion row has been created/updated.
     *
     * No-op for all JSON-based versions. Gwdm30Handler uses this to write
     * structured fields to dedicated SQL tables (linkages, accessibility, etc.).
     */
    public function afterStore(Dataset $dataset, DatasetVersion $dv, array $gwdm): void
    {
        // no-op for 2.x and below
    }

    /**
     * Called when reconstructing a version for a read response.
     *
     * Returns supplementary data to merge into the envelope — empty for JSON
     * versions; populated for Gwdm30Handler when reading from SQL tables.
     */
    public function afterRead(DatasetVersion $dv): array
    {
        return [];
    }

    /**
     * Pre-populate section data onto a DatasetVersion via setRelation() so that
     * afterRead() can access it without firing individual per-section queries.
     *
     * No-op for JSON-based versions. Gwdm30Handler overrides this to load all
     * structured SQL section tables. A future Gwdm31Handler would do the same.
     */
    public function preloadSections(DatasetVersion $dv): void
    {
        // no-op for 2.x and below
    }

    /**
     * Extract linkage data from this version and write it to the appropriate
     * junction tables. Called by the LinkageExtraction job.
     *
     * No-op for the base class. Gwdm2xHandler writes to the
     * dataset_version_has_dataset_version and publication_has_dataset_version
     * junction tables. Gwdm30Handler inherits this method unchanged.
     */
    public function extractLinkages(DatasetVersion $dv): void
    {
        // no-op for versions without linkage extraction support
    }

    /**
     * Whether datasets on this GWDM version should be indexed into Elasticsearch.
     */
    public function supportsElasticIndexing(): bool
    {
        return true;
    }

    /**
     * Extract Typesense-searchable fields from a reconstructed GWDM envelope.
     * Override when a version's field shapes diverge from delimited strings.
     */
    public function toSearchableFields(array $envelope): array
    {
        $keywords   = data_get($envelope, 'metadata.summary.keywords', '');
        $dataType   = data_get($envelope, 'metadata.summary.datasetType', '');
        $conformsTo = data_get($envelope, 'metadata.accessibility.formatAndStandards.conformsTo', '');
        $structural = data_get($envelope, 'metadata.structuralMetadata', []);

        if (is_string($structural)) {
            $structural = json_decode($structural, true) ?? [];
        }
        if (!is_array($structural)) {
            $structural = [];
        }

        return [
            'abstract' => data_get($envelope, 'metadata.summary.abstract', ''),
            'description' => data_get($envelope, 'metadata.summary.description', ''),
            'keywords' => array_values(array_filter(explode(';,;', $keywords))),
            'publisherName' => data_get(
                $envelope,
                'metadata.summary.publisher.name',
                data_get($envelope, 'metadata.summary.publisher.publisherName', '')
            ),
            'dataType' => array_values(array_filter(explode(';,;', $dataType))),
            'populationSize' => (int) data_get($envelope, 'metadata.summary.populationSize', -1),
            'datasetDOI' => data_get($envelope, 'metadata.summary.doiName', ''),
            'conformsTo' => array_values(array_filter(explode(';,;', $conformsTo))),
            'structuralTableNames' => collect($structural)
                ->pluck('name')
                ->filter(fn ($v) => is_string($v) && $v !== '')
                ->values()->all(),
            'structuralColumnNames' => collect($structural)
                ->flatMap(fn ($t) => collect($t['columns'] ?? [])->pluck('name'))
                ->filter(fn ($v) => is_string($v) && $v !== '')
                ->values()->all(),
            'structuralColumnDescriptions' => collect($structural)
                ->flatMap(fn ($t) => collect($t['columns'] ?? [])->pluck('description'))
                ->filter(fn ($v) => is_string($v) && $v !== '')
                ->values()->all(),
        ];
    }

    /**
     * Return the flat dataset-linkage list for a version (the frontend
     * `linkages` attribute: {title, url, dataset_id, linkage_type}).
     *
     * No-op for the base class. Gwdm2xHandler reads it from the
     * dataset_version_has_dataset_version junction table. Companion to
     * extractLinkages() (write) and afterRead() (nested metadata block) — all
     * three read/write the same handler-owned junction tables, so a
     * version-specific handler overrides them together.
     */
    public function getLinkages(int $datasetVersionId): array
    {
        return [];
    }

    // ── Onboarding form defaults ──────────────────────────────────────────────

    /**
     * GWDM dot-paths, relative to the raw dataset_versions.metadata envelope
     * (i.e. {gwdmVersion, metadata: {...GWDM...}, original_metadata}), used by
     * FormHydrationController::getDefaultValues() to pre-populate onboarding
     * form fields from a team's existing datasets.
     *
     * Shared across all JSON-based versions — the accessibility section shape
     * has not changed between GWDM < 1.1 and 2.x. Override this when a version
     * restructures these fields, or return [] when the fields are no longer
     * JSON-addressable (e.g. GWDM 3.0, where this data lives in SQL tables).
     */
    public function defaultValuePaths(): array
    {
        return [
            'dataUseLimitation'   => 'metadata.accessibility.usage.dataUseLimitation',
            'dataUseRequirements' => 'metadata.accessibility.usage.dataUseRequirements',
            'accessRights'        => 'metadata.accessibility.access.accessRights',
            'accessService'       => 'metadata.accessibility.access.accessService',
            'accessRequestCost'   => 'metadata.accessibility.access.accessRequestCost',
            'deliveryLeadTime'    => 'metadata.accessibility.access.deliveryLeadTime',
            'formats'             => 'metadata.accessibility.formatAndStandards.formats',
        ];
    }

    // ── Shared helpers ────────────────────────────────────────────────────────

    /**
     * Build the revisions array for the required block, covering all persisted
     * versions plus the new one about to be written.
     */
    protected function buildRevisions(Dataset $dataset, int $newVersionNumber): array
    {
        $existing = DatasetVersion::where('dataset_id', $dataset->id)
            ->orderBy('version')
            ->pluck('version')
            ->toArray();

        $all = array_unique(array_merge($existing, [$newVersionNumber]));
        sort($all);

        return array_map(fn (int $v) => [
            'url'     => config('gateway.gateway_url') . '/dataset/' . $dataset->id . '?version=' . $this->formatVersion($v),
            'version' => $this->formatVersion($v),
        ], $all);
    }

    protected function formatVersion(int $version): string
    {
        return "{$version}.0.0";
    }
}
