<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Gwdm30\DatasetVersionAccessibility;
use App\Models\Gwdm30\DatasetVersionCoverage;
use App\Models\Gwdm30\DatasetVersionObservation;
use App\Models\Gwdm30\DatasetVersionProvenance;
use App\Models\Gwdm30\DatasetVersionSummary;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Support\Facades\DB;

class Gwdm30PersistenceService
{
    /**
     * Persist all GWDM 3.0 structured fields from a post-TRASER GWDM array to
     * dedicated SQL tables. Called synchronously by afterStore() after the
     * DatasetVersion row is written.
     *
     * Dataset linkages are handled separately by LinkageExtraction (async job)
     * which calls Gwdm30Handler::extractLinkages() → Gwdm2xHandler::extractLinkages(),
     * writing to dataset_version_has_dataset_version.
     */
    public function persist(DatasetVersion $dv, array $gwdm): void
    {
        $access      = $gwdm['accessibility']['access'] ?? [];
        $usage       = $gwdm['accessibility']['usage'] ?? [];
        $fmts        = $gwdm['accessibility']['formatAndStandards'] ?? [];
        $summary     = $gwdm['summary'] ?? [];
        $coverage    = $gwdm['coverage'] ?? [];
        $origin      = $gwdm['provenance']['origin'] ?? [];
        $temporal    = $gwdm['provenance']['temporal'] ?? [];
        $observations = $gwdm['observations'] ?? [];

        DB::transaction(function () use ($dv, $access, $usage, $fmts, $summary, $coverage, $origin, $temporal, $observations) {
            // ── Accessibility ───────────────────────────────────────────────────
            DatasetVersionAccessibility::where('dataset_version_id', $dv->id)->delete();
            DatasetVersionAccessibility::create([
                'dataset_version_id'          => $dv->id,
                'access_rights'               => $access['accessRights'] ?? null,
                'access_service'              => $access['accessService'] ?? null,
                'access_request_cost'         => $access['accessRequestCost'] ?? null,
                'delivery_lead_time'          => $access['deliveryLeadTime'] ?? null,
                'jurisdiction'                => $access['jurisdiction'] ?? null,
                'data_controller'             => $access['dataController'] ?? null,
                'data_processor'              => $access['dataProcessor'] ?? null,
                'data_use_limitation'         => $this->ensureArray($usage['dataUseLimitation'] ?? null),
                'data_use_requirements'       => $this->ensureArray($usage['dataUseRequirements'] ?? null),
                'resource_creator'            => $usage['resourceCreator']['name'] ?? null,
                'vocabulary_encoding_schemes' => $fmts['vocabularyEncodingSchemes'] ?? null,
                'conforms_to'                 => $fmts['conformsTo'] ?? null,
                'languages'                   => $fmts['languages'] ?? null,
                'formats'                     => $this->ensureArray($fmts['formats'] ?? null),
            ]);

            // ── Summary ─────────────────────────────────────────────────────────
            DatasetVersionSummary::where('dataset_version_id', $dv->id)->delete();
            DatasetVersionSummary::create([
                'dataset_version_id'   => $dv->id,
                'abstract'             => $summary['abstract'] ?? null,
                'contact_point'        => $summary['contactPoint'] ?? null,
                'keywords'             => $summary['keywords'] ?? null,
                'controlled_keywords'  => $summary['controlledKeywords'] ?? null,
                'dataset_type'         => $summary['datasetType'] ?? null,
                'description'          => $summary['description'] ?? null,
                'doi_name'             => $summary['doiName'] ?? null,
                'publisher_name'       => $summary['publisher']['name'] ?? null,
                'publisher_gateway_id' => $summary['publisher']['gatewayId'] ?? null,
                'population_size'      => isset($summary['populationSize']) ? (int) $summary['populationSize'] : null,
            ]);

            // ── Coverage ─────────────────────────────────────────────────────────
            DatasetVersionCoverage::where('dataset_version_id', $dv->id)->delete();
            DatasetVersionCoverage::create([
                'dataset_version_id' => $dv->id,
                'spatial'            => $coverage['spatial'] ?? null,
                'typical_age_range'  => $coverage['typicalAgeRange'] ?? null,
                'pathway'            => $coverage['pathway'] ?? null,
                'followup'           => $coverage['followup'] ?? null,
            ]);

            // ── Provenance ───────────────────────────────────────────────────────
            DatasetVersionProvenance::where('dataset_version_id', $dv->id)->delete();
            DatasetVersionProvenance::create([
                'dataset_version_id'           => $dv->id,
                'origin_purpose'               => $origin['purpose'] ?? null,
                'origin_source'                => $origin['source'] ?? null,
                'origin_collection_situation'  => $origin['collectionSituation'] ?? null,
                'temporal_start_date'          => $temporal['startDate'] ?? null,
                'temporal_end_date'            => $temporal['endDate'] ?? null,
                'temporal_time_lag'            => $temporal['timeLag'] ?? null,
                'temporal_accrual_periodicity' => $temporal['accrualPeriodicity'] ?? null,
            ]);

            // ── Observations (array → many rows) ─────────────────────────────────
            DatasetVersionObservation::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $observations as $obs) {
                if (!is_array($obs) || empty($obs['observedNode'])) {
                    continue;
                }
                DatasetVersionObservation::create([
                    'dataset_version_id'          => $dv->id,
                    'observed_node'               => $obs['observedNode'],
                    'measured_value'              => $obs['measuredValue'] ?? null,
                    'observation_date'            => $obs['observationDate'] ?? null,
                    'measured_property'           => $obs['measuredProperty'] ?? null,
                    'disambiguating_description'  => $obs['disambiguatingDescription'] ?? null,
                ]);
            }
        });
    }

    /**
     * Read all GWDM 3.0 structured fields from SQL and return them in
     * schema-conformant shape for merging into the GWDM metadata envelope.
     *
     * Contract: returns all keys for a section or omits the section entirely.
     * Missing sections fall through to the JSON blob at the call site.
     * Returns [] if no SQL rows exist for this version.
     */
    public function read(DatasetVersion $dv): array
    {
        return array_merge(
            $this->readSummary($dv),
            $this->readCoverage($dv),
            $this->readProvenance($dv),
            $this->readAccessibility($dv),
            $this->readObservations($dv),
            $this->readLinkages($dv),
        );
    }

    private function readSummary(DatasetVersion $dv): array
    {
        $row = DatasetVersionSummary::where('dataset_version_id', $dv->id)->first();
        if (!$row) {
            return [];
        }

        // title and shortTitle live in dataset_versions columns, not gwdm30_summary.
        // Carry them forward from the stored JSON blob so the merged summary is complete.
        $blobSummary = $dv->metadata['metadata']['summary'] ?? [];

        return [
            'summary' => array_merge($blobSummary, [
                'abstract'           => $row->abstract,
                'contactPoint'       => $row->contact_point,
                'keywords'           => $row->keywords,
                'controlledKeywords' => $row->controlled_keywords,
                'datasetType'        => $row->dataset_type,
                'description'        => $row->description,
                'doiName'            => $row->doi_name,
                'publisher'          => [
                    'name'      => $row->publisher_name,
                    'gatewayId' => $row->publisher_gateway_id,
                ],
                'populationSize'     => $row->population_size,
            ]),
        ];
    }

    private function readCoverage(DatasetVersion $dv): array
    {
        $row = DatasetVersionCoverage::where('dataset_version_id', $dv->id)->first();
        if (!$row) {
            return [];
        }

        return [
            'coverage' => [
                'spatial'        => $row->spatial,
                'typicalAgeRange' => $row->typical_age_range,
                'pathway'        => $row->pathway,
                'followup'       => $row->followup,
            ],
        ];
    }

    private function readProvenance(DatasetVersion $dv): array
    {
        $row = DatasetVersionProvenance::where('dataset_version_id', $dv->id)->first();
        if (!$row) {
            return [];
        }

        return [
            'provenance' => [
                'origin' => [
                    'purpose'             => $row->origin_purpose,
                    'source'              => $row->origin_source,
                    'collectionSituation' => $row->origin_collection_situation,
                ],
                'temporal' => [
                    'startDate'           => $row->temporal_start_date?->toDateString(),
                    'endDate'             => $row->temporal_end_date?->toDateString(),
                    'timeLag'             => $row->temporal_time_lag,
                    'accrualPeriodicity'  => $row->temporal_accrual_periodicity,
                ],
            ],
        ];
    }

    private function readAccessibility(DatasetVersion $dv): array
    {
        $row = DatasetVersionAccessibility::where('dataset_version_id', $dv->id)->first();
        if (!$row) {
            return [];
        }

        return [
            'accessibility' => [
                'access' => [
                    'accessRights'      => $row->access_rights,
                    'accessService'     => $row->access_service,
                    'accessRequestCost' => $row->access_request_cost,
                    'deliveryLeadTime'  => $row->delivery_lead_time,
                    'jurisdiction'      => $row->jurisdiction,
                    'dataController'    => $row->data_controller,
                    'dataProcessor'     => $row->data_processor,
                ],
                'usage' => [
                    'dataUseLimitation'   => $row->data_use_limitation,
                    'dataUseRequirements' => $row->data_use_requirements,
                    'resourceCreator'     => ['name' => $row->resource_creator],
                ],
                'formatAndStandards' => [
                    'vocabularyEncodingSchemes' => $row->vocabulary_encoding_schemes,
                    'conformsTo'                => $row->conforms_to,
                    'languages'                 => $row->languages,
                    'formats'                   => $row->formats,
                ],
            ],
        ];
    }

    private function readObservations(DatasetVersion $dv): array
    {
        $rows = DatasetVersionObservation::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'observations' => $rows->map(fn ($obs) => [
                'observedNode'              => $obs->observed_node,
                'measuredValue'             => $obs->measured_value,
                'observationDate'           => $obs->observation_date?->toDateString(),
                'measuredProperty'          => $obs->measured_property,
                'disambiguatingDescription' => $obs->disambiguating_description,
            ])->values()->all(),
        ];
    }

    /**
     * Read dataset + publication linkages from the shared junction tables.
     *
     * Mirrors Gwdm2xHandler::afterRead() — both 2.x and 3.0 datasets write
     * linkages to dataset_version_has_dataset_version / publication_has_dataset_version
     * so the read logic is identical. Returns [] when no extracted rows exist,
     * falling through to the JSON blob.
     */
    private function readLinkages(DatasetVersion $dv): array
    {
        $resolvedDatasets = DatasetVersionHasDatasetVersion::query()
            ->where('dataset_version_has_dataset_version.dataset_version_source_id', $dv->id)
            ->where('dataset_version_has_dataset_version.direct_linkage', 1)
            ->where('dataset_version_has_dataset_version.description', Gwdm2xHandler::LINKAGE_DESCRIPTION)
            ->whereNotNull('dataset_version_has_dataset_version.dataset_version_target_id')
            ->join('dataset_versions as tdv', 'tdv.id', '=', 'dataset_version_has_dataset_version.dataset_version_target_id')
            ->join('datasets as td', 'td.id', '=', 'tdv.dataset_id')
            ->select(
                'dataset_version_has_dataset_version.linkage_type',
                'tdv.short_title',
                'td.pid',
                'td.id as dataset_id',
            )
            ->get();

        $unresolvedDatasets = DatasetVersionHasDatasetVersion::query()
            ->where('dataset_version_source_id', $dv->id)
            ->where('direct_linkage', 1)
            ->where('description', Gwdm2xHandler::LINKAGE_DESCRIPTION)
            ->whereNull('dataset_version_target_id')
            ->select('linkage_type', 'raw_url', 'raw_pid', 'raw_title')
            ->get();

        $publications = PublicationHasDatasetVersion::query()
            ->where('publication_has_dataset_version.dataset_version_id', $dv->id)
            ->where('publication_has_dataset_version.description', Gwdm2xHandler::LINKAGE_DESCRIPTION)
            ->leftJoin('publications', 'publications.id', '=', 'publication_has_dataset_version.publication_id')
            ->select(
                'publication_has_dataset_version.link_type',
                'publications.paper_doi',
                'publication_has_dataset_version.raw_doi',
            )
            ->get();

        if ($resolvedDatasets->isEmpty() && $unresolvedDatasets->isEmpty() && $publications->isEmpty()) {
            return [];
        }

        $datasetLinkage = [];
        foreach ($resolvedDatasets as $row) {
            $datasetLinkage[$row->linkage_type][] = [
                'url'   => config('gateway.gateway_url') . '/en/dataset/' . $row->dataset_id,
                'pid'   => $row->pid,
                'title' => $row->short_title,
            ];
        }
        foreach ($unresolvedDatasets as $row) {
            $datasetLinkage[$row->linkage_type][] = [
                'url'   => $row->raw_url,
                'pid'   => $row->raw_pid,
                'title' => $row->raw_title,
            ];
        }

        $aboutDataset = [];
        $usingDataset = [];
        foreach ($publications as $row) {
            $doi = $row->paper_doi ?? $row->raw_doi;
            if (!$doi) {
                continue;
            }
            if ($row->link_type === 'ABOUT') {
                $aboutDataset[] = $doi;
            } else {
                $usingDataset[] = $doi;
            }
        }

        return [
            'linkage' => [
                'datasetLinkage'          => $datasetLinkage,
                'publicationAboutDataset' => $aboutDataset,
                'publicationUsingDataset' => $usingDataset,
            ],
        ];
    }

    /** Ensure a value that may arrive as a string (GWDM 2.1 legacy) is returned as an array. */
    private function ensureArray(mixed $value): ?array
    {
        if (is_null($value)) {
            return null;
        }
        return is_array($value) ? $value : [$value];
    }
}
