<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Gwdm30\Accessibility;
use App\Models\Gwdm30\Coverage;
use App\Models\Gwdm30\DemographicFrequency;
use App\Models\Gwdm30\Distribution;
use App\Models\Gwdm30\LinkageMeta;
use App\Models\Gwdm30\Observation;
use App\Models\Gwdm30\Omics;
use App\Models\Gwdm30\Provenance;
use App\Models\Gwdm30\QualityAnnotation;
use App\Models\Gwdm30\Summary;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Support\Facades\DB;

/**
 * Handler for GWDM 3.0.
 *
 * Extends Gwdm2xHandler because the 3.0 metadata schema is a superset of
 * 2.x: the required block, publisher field format, and linkage extraction
 * are all identical. Only the persistence strategy differs — every GWDM
 * section is written to dedicated SQL tables rather than solely to the
 * JSON metadata blob.
 *
 * extractLinkages() is inherited from Gwdm2xHandler unchanged — both 2.x
 * and 3.0 write dataset linkages to dataset_version_has_dataset_version.
 */
class Gwdm30Handler extends Gwdm2xHandler
{
    public function afterStore(Dataset $dataset, DatasetVersion $dv, array $gwdm): void
    {
        $this->persist($dv, $gwdm);
    }

    public function afterRead(DatasetVersion $dv): array
    {
        return $this->read($dv);
    }

    // ── Write path ────────────────────────────────────────────────────────────

    private function persist(DatasetVersion $dv, array $gwdm): void
    {
        $access = $gwdm['accessibility']['access'] ?? [];
        $usage = $gwdm['accessibility']['usage'] ?? [];
        $fmts = $gwdm['accessibility']['formatAndStandards'] ?? [];
        $summary = $gwdm['summary'] ?? [];
        $coverage = $gwdm['coverage'] ?? [];
        $origin = $gwdm['provenance']['origin'] ?? [];
        $temporal = $gwdm['provenance']['temporal'] ?? [];
        $retention = $gwdm['provenance']['retentionPeriod'] ?? [];
        $observations = $gwdm['observations'] ?? [];
        $linkageMeta = $gwdm['linkage'] ?? [];
        $omics = $gwdm['omics'] ?? [];
        $demo = $gwdm['demographicFrequency'] ?? [];
        $distributions = $gwdm['distributions'] ?? [];
        $qualityAnnotations = $gwdm['qualityAnnotations'] ?? [];

        DB::transaction(function () use ($dv, $access, $usage, $fmts, $summary, $coverage, $origin, $temporal, $retention, $observations, $linkageMeta, $omics, $demo, $distributions, $qualityAnnotations) {
            Accessibility::where('dataset_version_id', $dv->id)->delete();
            Accessibility::create([
                'dataset_version_id' => $dv->id,
                'access_rights' => $access['accessRights'] ?? null,
                'access_service' => $access['accessService'] ?? null,
                'access_service_category' => $access['accessServiceCategory'] ?? null,
                'access_request_cost' => $access['accessRequestCost'] ?? null,
                'delivery_lead_time' => $access['deliveryLeadTime'] ?? null,
                'jurisdiction' => $access['jurisdiction'] ?? null,
                'data_controller' => $access['dataController'] ?? null,
                'data_processor' => $access['dataProcessor'] ?? null,
                'legal_basis' => $access['legalBasis'] ?? null,
                'personal_data' => $access['personalData'] ?? null,
                'applicable_legislation' => $access['applicableLegislation'] ?? null,
                'data_use_limitation' => $this->ensureArray($usage['dataUseLimitation'] ?? null),
                'data_use_requirements' => $this->ensureArray($usage['dataUseRequirements'] ?? null),
                'resource_creator' => $usage['resourceCreator']['name'] ?? null,
                'vocabulary_encoding_schemes' => $fmts['vocabularyEncodingSchemes'] ?? null,
                'conforms_to' => $fmts['conformsTo'] ?? null,
                'languages' => $fmts['languages'] ?? null,
                'formats' => $this->ensureArray($fmts['formats'] ?? null),
            ]);

            Summary::where('dataset_version_id', $dv->id)->delete();
            Summary::create([
                'dataset_version_id' => $dv->id,
                'abstract' => $summary['abstract'] ?? null,
                'contact_point' => $summary['contactPoint'] ?? null,
                'keywords' => $summary['keywords'] ?? null,
                'controlled_keywords' => $summary['controlledKeywords'] ?? null,
                'dataset_type' => $summary['datasetType'] ?? null,
                'dataset_sub_type' => $summary['datasetSubType'] ?? null,
                'in_pipeline' => $summary['inPipeline'] ?? null,
                'funders' => $summary['funders'] ?? null,
                'description' => $summary['description'] ?? null,
                'doi_name' => $summary['doiName'] ?? null,
                'license_url' => $summary['licenseUrl'] ?? null,
                'landing_page' => $summary['landingPage'] ?? null,
                'creator_name' => $summary['creator']['name'] ?? null,
                'creator_ror_id' => $summary['creator']['rorId'] ?? null,
                'creator_orcid_id' => $summary['creator']['orcidId'] ?? null,
                'creator_gateway_id' => $summary['creator']['gatewayId'] ?? null,
                'theme' => $summary['theme'] ?? null,
                'publisher_name' => $summary['publisher']['name'] ?? null,
                'publisher_gateway_id' => $summary['publisher']['gatewayId'] ?? null,
                'publisher_ror_id' => $summary['publisher']['rorId'] ?? null,
                'population_size' => isset($summary['populationSize']) ? (int) $summary['populationSize'] : null,
            ]);

            Coverage::where('dataset_version_id', $dv->id)->delete();
            Coverage::create([
                'dataset_version_id' => $dv->id,
                'spatial' => $coverage['spatial'] ?? null,
                'min_typical_age' => isset($coverage['minTypicalAge']) ? (int) $coverage['minTypicalAge'] : null,
                'max_typical_age' => isset($coverage['maxTypicalAge']) ? (int) $coverage['maxTypicalAge'] : null,
                'population_coverage' => $coverage['populationCoverage'] ?? null,
                'number_of_unique_individuals' => isset($coverage['numberOfUniqueIndividuals']) ? (int) $coverage['numberOfUniqueIndividuals'] : null,
                'number_of_records' => isset($coverage['numberOfRecords']) ? (int) $coverage['numberOfRecords'] : null,
                'pathway' => $coverage['pathway'] ?? null,
                'followup' => $coverage['followUp'] ?? null,
                'dataset_completeness' => $coverage['datasetCompleteness'] ?? null,
            ]);

            Provenance::where('dataset_version_id', $dv->id)->delete();
            Provenance::create([
                'dataset_version_id' => $dv->id,
                'origin_purpose' => $origin['purpose'] ?? null,
                'origin_source' => $origin['source'] ?? null,
                'origin_collection_situation' => $origin['collectionSituation'] ?? null,
                'origin_image_contrast' => $origin['imageContrast'] ?? null,
                'temporal_start_date' => $temporal['startDate'] ?? null,
                'temporal_end_date' => $temporal['endDate'] ?? null,
                'temporal_time_lag' => $temporal['timeLag'] ?? null,
                'temporal_accrual_periodicity' => $temporal['accrualPeriodicity'] ?? null,
                'temporal_distribution_release_date' => $temporal['distributionReleaseDate'] ?? null,
                'retention_period_start' => $retention['startDate'] ?? null,
                'retention_period_end' => $retention['endDate'] ?? null,
            ]);

            Observation::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $observations as $obs) {
                if (! is_array($obs) || empty($obs['observedNode'])) {
                    continue;
                }
                Observation::create([
                    'dataset_version_id' => $dv->id,
                    'observed_node' => $obs['observedNode'],
                    'measured_value' => $obs['measuredValue'] ?? null,
                    'observation_date' => $obs['observationDate'] ?? null,
                    'measured_property' => $obs['measuredProperty'] ?? null,
                    'disambiguating_description' => $obs['disambiguatingDescription'] ?? null,
                ]);
            }

            LinkageMeta::where('dataset_version_id', $dv->id)->delete();
            LinkageMeta::create([
                'dataset_version_id' => $dv->id,
                'is_generated_using' => $linkageMeta['isGeneratedUsing'] ?? null,
                'associated_media' => $linkageMeta['associatedMedia'] ?? null,
                'data_uses' => $linkageMeta['dataUses'] ?? null,
                'is_reference_in' => $linkageMeta['isReferenceIn'] ?? null,
                'tools' => $linkageMeta['tools'] ?? null,
                'investigations' => $linkageMeta['investigations'] ?? null,
                'synthetic_data_web_link' => $linkageMeta['syntheticDataWebLink'] ?? null,
            ]);

            Omics::where('dataset_version_id', $dv->id)->delete();
            Omics::create([
                'dataset_version_id' => $dv->id,
                'assay' => $omics['assay'] ?? null,
                'platform' => $omics['platform'] ?? null,
            ]);

            DemographicFrequency::where('dataset_version_id', $dv->id)->delete();
            foreach (($demo['age'] ?? []) as $row) {
                DemographicFrequency::create([
                    'dataset_version_id' => $dv->id,
                    'category' => 'age',
                    'bin' => $row['bin'],
                    'bin_vocabulary' => null,
                    'count' => $row['count'],
                ]);
            }
            foreach (($demo['ethnicity'] ?? []) as $row) {
                DemographicFrequency::create([
                    'dataset_version_id' => $dv->id,
                    'category' => 'ethnicity',
                    'bin' => $row['bin'],
                    'bin_vocabulary' => null,
                    'count' => $row['count'],
                ]);
            }
            foreach (($demo['disease'] ?? []) as $row) {
                DemographicFrequency::create([
                    'dataset_version_id' => $dv->id,
                    'category' => 'disease',
                    'bin' => $row['diseaseCode'],
                    'bin_vocabulary' => $row['diseaseCodeVocabulary'] ?? null,
                    'count' => $row['count'],
                ]);
            }

            Distribution::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $distributions as $dist) {
                if (! is_array($dist)) {
                    continue;
                }
                Distribution::create([
                    'dataset_version_id' => $dv->id,
                    'title' => $dist['title'] ?? null,
                    'description' => $dist['description'] ?? null,
                    'access_url' => $dist['accessUrl'] ?? null,
                    'download_url' => $dist['downloadUrl'] ?? null,
                    'media_type' => $dist['mediaType'] ?? null,
                    'format' => $dist['format'] ?? null,
                    'byte_size' => isset($dist['byteSize']) ? (int) $dist['byteSize'] : null,
                    'license_url' => $dist['licenseUrl'] ?? null,
                    'access_service' => $dist['accessService'] ?? null,
                    'issued' => $dist['issued'] ?? null,
                    'modified' => $dist['modified'] ?? null,
                ]);
            }

            QualityAnnotation::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $qualityAnnotations as $qa) {
                if (! is_array($qa)) {
                    continue;
                }
                QualityAnnotation::create([
                    'dataset_version_id' => $dv->id,
                    'annotation_type' => $qa['annotationType'] ?? null,
                    'quality_dimension' => $qa['qualityDimension'] ?? null,
                    'quality_value' => $qa['qualityValue'] ?? null,
                    'quality_description' => $qa['qualityDescription'] ?? null,
                    'certification_url' => $qa['certificationUrl'] ?? null,
                    'annotation_date' => $qa['annotationDate'] ?? null,
                ]);
            }
        });
    }

    // ── Read path ─────────────────────────────────────────────────────────────

    private function read(DatasetVersion $dv): array
    {
        $sections = array_merge(
            $this->readSummary($dv),
            $this->readCoverage($dv),
            $this->readProvenance($dv),
            $this->readAccessibility($dv),
            $this->readObservations($dv),
            $this->readOmics($dv),
            $this->readDemographicFrequency($dv),
            $this->readDistributions($dv),
            $this->readQualityAnnotations($dv),
        );

        $linkageSection = $this->readLinkages($dv);
        $linkageMetaSection = $this->readLinkageMeta($dv);

        if (! empty($linkageSection) || ! empty($linkageMetaSection)) {
            $sections['linkage'] = array_merge(
                $linkageSection['linkage'] ?? [],
                $linkageMetaSection['linkage'] ?? [],
            );
        }

        return $sections;
    }

    private function readSummary(DatasetVersion $dv): array
    {
        $row = Summary::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'summary' => [
                'title' => $dv->title,
                'shortTitle' => $dv->short_title,
                'abstract' => $row->abstract,
                'contactPoint' => $row->contact_point,
                'keywords' => $row->keywords,
                'controlledKeywords' => $row->controlled_keywords,
                'datasetType' => $row->dataset_type,
                'datasetSubType' => $row->dataset_sub_type,
                'inPipeline' => $row->in_pipeline,
                'funders' => $row->funders,
                'description' => $row->description,
                'doiName' => $row->doi_name,
                'licenseUrl' => $row->license_url,
                'landingPage' => $row->landing_page,
                'creator' => [
                    'name' => $row->creator_name,
                    'rorId' => $row->creator_ror_id,
                    'orcidId' => $row->creator_orcid_id,
                    'gatewayId' => $row->creator_gateway_id,
                ],
                'theme' => $row->theme,
                'publisher' => [
                    'name' => $row->publisher_name,
                    'gatewayId' => $row->publisher_gateway_id,
                    'rorId' => $row->publisher_ror_id,
                ],
                'populationSize' => $row->population_size,
            ],
        ];
    }

    private function readCoverage(DatasetVersion $dv): array
    {
        $row = Coverage::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'coverage' => [
                'spatial' => $row->spatial,
                'minTypicalAge' => $row->min_typical_age,
                'maxTypicalAge' => $row->max_typical_age,
                'populationCoverage' => $row->population_coverage,
                'numberOfUniqueIndividuals' => $row->number_of_unique_individuals,
                'numberOfRecords' => $row->number_of_records,
                'pathway' => $row->pathway,
                'followUp' => $row->followup,
                'datasetCompleteness' => $row->dataset_completeness,
            ],
        ];
    }

    private function readProvenance(DatasetVersion $dv): array
    {
        $row = Provenance::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'provenance' => [
                'origin' => [
                    'purpose' => $row->origin_purpose,
                    'source' => $row->origin_source,
                    'collectionSituation' => $row->origin_collection_situation,
                    'imageContrast' => $row->origin_image_contrast,
                ],
                'temporal' => [
                    'startDate' => $row->temporal_start_date?->toDateString(),
                    'endDate' => $row->temporal_end_date?->toDateString(),
                    'timeLag' => $row->temporal_time_lag,
                    'accrualPeriodicity' => $row->temporal_accrual_periodicity,
                    'distributionReleaseDate' => $row->temporal_distribution_release_date?->toDateString(),
                ],
                'retentionPeriod' => [
                    'startDate' => $row->retention_period_start?->toDateString(),
                    'endDate' => $row->retention_period_end?->toDateString(),
                ],
            ],
        ];
    }

    private function readAccessibility(DatasetVersion $dv): array
    {
        $row = Accessibility::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'accessibility' => [
                'access' => [
                    'accessRights' => $row->access_rights,
                    'accessService' => $row->access_service,
                    'accessServiceCategory' => $row->access_service_category,
                    'accessRequestCost' => $row->access_request_cost,
                    'deliveryLeadTime' => $row->delivery_lead_time,
                    'jurisdiction' => $row->jurisdiction,
                    'dataController' => $row->data_controller,
                    'dataProcessor' => $row->data_processor,
                    'legalBasis' => $row->legal_basis,
                    'personalData' => $row->personal_data,
                    'applicableLegislation' => $row->applicable_legislation,
                ],
                'usage' => [
                    'dataUseLimitation' => $row->data_use_limitation,
                    'dataUseRequirements' => $row->data_use_requirements,
                    'resourceCreator' => ['name' => $row->resource_creator],
                ],
                'formatAndStandards' => [
                    'vocabularyEncodingSchemes' => $row->vocabulary_encoding_schemes,
                    'conformsTo' => $row->conforms_to,
                    'languages' => $row->languages,
                    'formats' => $row->formats,
                ],
            ],
        ];
    }

    private function readObservations(DatasetVersion $dv): array
    {
        $rows = Observation::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'observations' => $rows->map(fn ($obs) => [
                'observedNode' => $obs->observed_node,
                'measuredValue' => $obs->measured_value,
                'observationDate' => $obs->observation_date?->toDateString(),
                'measuredProperty' => $obs->measured_property,
                'disambiguatingDescription' => $obs->disambiguating_description,
            ])->values()->all(),
        ];
    }

    private function readLinkageMeta(DatasetVersion $dv): array
    {
        $row = LinkageMeta::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'linkage' => [
                'isGeneratedUsing' => $row->is_generated_using,
                'associatedMedia' => $row->associated_media,
                'dataUses' => $row->data_uses,
                'isReferenceIn' => $row->is_reference_in,
                'tools' => $row->tools,
                'investigations' => $row->investigations,
                'syntheticDataWebLink' => $row->synthetic_data_web_link,
            ],
        ];
    }

    private function readOmics(DatasetVersion $dv): array
    {
        $row = Omics::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'omics' => [
                'assay' => $row->assay,
                'platform' => $row->platform,
            ],
        ];
    }

    private function readDemographicFrequency(DatasetVersion $dv): array
    {
        $rows = DemographicFrequency::where('dataset_version_id', $dv->id)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $age = [];
        $ethnicity = [];
        $disease = [];

        foreach ($rows as $row) {
            if ($row->category === 'age') {
                $age[] = ['bin' => $row->bin, 'count' => $row->count];
            } elseif ($row->category === 'ethnicity') {
                $ethnicity[] = ['bin' => $row->bin, 'count' => $row->count];
            } elseif ($row->category === 'disease') {
                $disease[] = [
                    'diseaseCode' => $row->bin,
                    'diseaseCodeVocabulary' => $row->bin_vocabulary,
                    'count' => $row->count,
                ];
            }
        }

        return [
            'demographicFrequency' => [
                'age' => $age,
                'ethnicity' => $ethnicity,
                'disease' => $disease,
            ],
        ];
    }

    private function readDistributions(DatasetVersion $dv): array
    {
        $rows = Distribution::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'distributions' => $rows->map(fn ($dist) => [
                'title' => $dist->title,
                'description' => $dist->description,
                'accessUrl' => $dist->access_url,
                'downloadUrl' => $dist->download_url,
                'mediaType' => $dist->media_type,
                'format' => $dist->format,
                'byteSize' => $dist->byte_size,
                'licenseUrl' => $dist->license_url,
                'accessService' => $dist->access_service,
                'issued' => $dist->issued?->toDateString(),
                'modified' => $dist->modified?->toDateString(),
            ])->values()->all(),
        ];
    }

    private function readQualityAnnotations(DatasetVersion $dv): array
    {
        $rows = QualityAnnotation::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'qualityAnnotations' => $rows->map(fn ($qa) => [
                'annotationType' => $qa->annotation_type,
                'qualityDimension' => $qa->quality_dimension,
                'qualityValue' => $qa->quality_value,
                'qualityDescription' => $qa->quality_description,
                'certificationUrl' => $qa->certification_url,
                'annotationDate' => $qa->annotation_date?->toDateString(),
            ])->values()->all(),
        ];
    }

    private function readLinkages(DatasetVersion $dv): array
    {
        $resolvedDatasets = DatasetVersionHasDatasetVersion::query()
            ->where('dataset_version_has_dataset_version.dataset_version_source_id', $dv->id)
            ->where('dataset_version_has_dataset_version.direct_linkage', 1)
            ->where('dataset_version_has_dataset_version.description', self::LINKAGE_DESCRIPTION)
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
            ->where('description', self::LINKAGE_DESCRIPTION)
            ->whereNull('dataset_version_target_id')
            ->select('linkage_type', 'raw_url', 'raw_pid', 'raw_title')
            ->get();

        $publications = PublicationHasDatasetVersion::query()
            ->where('publication_has_dataset_version.dataset_version_id', $dv->id)
            ->where('publication_has_dataset_version.description', self::LINKAGE_DESCRIPTION)
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
                'url' => config('gateway.gateway_url').'/en/dataset/'.$row->dataset_id,
                'pid' => $row->pid,
                'title' => $row->short_title,
            ];
        }
        foreach ($unresolvedDatasets as $row) {
            $datasetLinkage[$row->linkage_type][] = [
                'url' => $row->raw_url,
                'pid' => $row->raw_pid,
                'title' => $row->raw_title,
            ];
        }

        $aboutDataset = [];
        $usingDataset = [];
        foreach ($publications as $row) {
            $doi = $row->paper_doi ?? $row->raw_doi;
            if (! $doi) {
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
                'datasetLinkage' => $datasetLinkage,
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
