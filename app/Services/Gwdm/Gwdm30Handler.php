<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Gwdm30\Accessibility;
use App\Models\Gwdm30\Coverage;
use App\Models\Gwdm30\DatasetFilter;
use App\Models\Gwdm30\DemographicFrequency;
use App\Models\Gwdm30\Distribution;
use App\Models\Gwdm30\Erd;
use App\Models\Gwdm30\LinkageMeta;
use App\Models\Gwdm30\Observation;
use App\Models\Gwdm30\Omics;
use App\Models\Gwdm30\ProjectGrant;
use App\Models\Gwdm30\Provenance;
use App\Models\Gwdm30\QualityAnnotation;
use App\Models\Gwdm30\Required;
use App\Models\Gwdm30\StructuralColumn;
use App\Models\Gwdm30\StructuralTable;
use App\Models\Gwdm30\StructuralValue;
use App\Models\Gwdm30\Summary;
use App\Models\Gwdm30\TissueSampleCollection;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Support\Collection;
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

        // Write dataset/publication linkage junctions synchronously from the input
        // metadata. Unlike 2.x, these arrays are NOT recoverable later: persist()
        // stores only the linkage *meta* subset, and readLinkages() rebuilds the
        // datasetLinkage/publication sections from the junction tables themselves.
        // The async LinkageExtraction job (extractLinkages) is therefore a no-op
        // for 3.0 — see the override below.
        $this->writeLinkages($dv, $gwdm);

        // Denormalise the reconstructed sections into the metadata JSON column so a
        // caller that opts in (X-Gwdm-Cache: bypass) can read the whole envelope in
        // one JSON read — parity with 2.x — instead of reassembling ~16 SQL tables.
        // The SQL tables remain the source of truth (and the default read path);
        // this column is a derived read copy, rewritten on every write in the same
        // transaction, so it never drifts on in-band writes.
        //
        // Sections are read back FROM SQL (not from the input $gwdm) so the stored
        // JSON is byte-identical to what reconstructing from SQL produces — the
        // readXxx() helpers apply coercions the raw input does not. Linkage is EXCLUDED: it
        // tracks other datasets' latest versions and must be merged fresh on read
        // (see readLinkageOnly()).
        $this->refreshSectionCache($dv);
    }

    /**
     * (Re)write the denormalised section cache into $dv->metadata['metadata']
     * from the current SQL section rows. Idempotent — safe to call from
     * afterStore() and from the backfill command. Uses saveQuietly() so it does
     * not fire a second "updated" model event (indexing already fires on create).
     */
    public function refreshSectionCache(DatasetVersion $dv): void
    {
        $stored = is_array($dv->metadata) ? $dv->metadata : [];
        $stored['metadata'] = $this->readSections($dv);
        $dv->metadata = $stored;
        $dv->saveQuietly();
    }

    /**
     * No-op for GWDM 3.0: linkage junctions are written synchronously in
     * afterStore() from the input metadata (see above). Running the async job's
     * reconstruction here would read the junctions back from themselves and do
     * redundant work.
     */
    public function extractLinkages(DatasetVersion $dv): void
    {
        // intentionally empty
    }

    public function afterRead(DatasetVersion $dv): array
    {
        // When the caller opted into the cache (X-Gwdm-Cache: bypass) and this row
        // carries it, the sections come from extractStoredGwdm() and afterRead()
        // only merges the always-fresh linkage on top. Otherwise — the default, or
        // an un-backfilled row — reconstruct in full from SQL (the source of truth).
        if ($this->hasCachedSections($dv)) {
            return $this->readLinkageOnly($dv);
        }

        return $this->read($dv);
    }

    public function buildEnvelope(array $gwdm, array $originalMetadata): array
    {
        // The section cache ('metadata' key) is written afterwards in afterStore()
        // once persist() has populated the SQL tables — see refreshSectionCache().
        return [
            'gwdmVersion'       => $this->version(),
            'original_metadata' => $originalMetadata,
        ];
    }

    public function extractStoredGwdm(array $storedData): array
    {
        // The denormalised section cache lives under the 'metadata' key (mirroring
        // 2.x). Only served when the caller opted in via `X-Gwdm-Cache: bypass`;
        // by default (and on un-backfilled rows) return [] so afterRead()
        // reconstructs from the SQL section tables.
        if (! $this->cacheRequested()) {
            return [];
        }

        return $storedData['metadata'] ?? [];
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
        $required = $gwdm['required'] ?? [];
        $structuralMetadata = $gwdm['structuralMetadata'] ?? [];
        $tissuesSampleCollection = $gwdm['tissuesSampleCollection'] ?? [];
        $projectGrants = $gwdm['projectGrants'] ?? [];
        $datasetFilters = $gwdm['datasetFilters'] ?? [];
        $erd = $gwdm['erd'] ?? [];

        DB::transaction(function () use ($dv, $access, $usage, $fmts, $summary, $coverage, $origin, $temporal, $retention, $observations, $linkageMeta, $omics, $demo, $distributions, $qualityAnnotations, $required, $structuralMetadata, $tissuesSampleCollection, $projectGrants, $datasetFilters, $erd) {
            Accessibility::where('dataset_version_id', $dv->id)->delete();
            Accessibility::create([
                'dataset_version_id' => $dv->id,
                'access_rights' => $this->ensureArray($access['accessRights'] ?? null),
                'access_service' => $access['accessService'] ?? null,
                'access_service_category' => $this->ensureArray($access['accessServiceCategory'] ?? null),
                'access_request_cost' => $access['accessRequestCost'] ?? null,
                'delivery_lead_time' => $access['deliveryLeadTime'] ?? null,
                'jurisdiction' => $this->ensureArray($access['jurisdiction'] ?? null),
                'data_controller' => $access['dataController'] ?? null,
                'data_processor' => $access['dataProcessor'] ?? null,
                'legal_basis' => $access['legalBasis'] ?? null,
                'personal_data' => $access['personalData'] ?? null,
                'applicable_legislation' => $access['applicableLegislation'] ?? null,
                'data_use_limitation' => $this->ensureArray($usage['dataUseLimitation'] ?? null),
                'data_use_requirements' => $this->ensureArray($usage['dataUseRequirements'] ?? null),
                'resource_creator' => $usage['resourceCreator']['name'] ?? null,
                'resource_creator_gateway_id' => $usage['resourceCreator']['gatewayId'] ?? null,
                'resource_creator_ror_id' => $usage['resourceCreator']['rorId'] ?? null,
                'vocabulary_encoding_schemes' => $this->ensureArray($fmts['vocabularyEncodingSchemes'] ?? null),
                'conforms_to' => $this->ensureArray($fmts['conformsTo'] ?? null),
                'languages' => $this->ensureArray($fmts['languages'] ?? null),
                'formats' => $this->ensureArray($fmts['formats'] ?? null),
            ]);

            Summary::where('dataset_version_id', $dv->id)->delete();
            Summary::create([
                'dataset_version_id' => $dv->id,
                'abstract' => $summary['abstract'] ?? null,
                'contact_point' => $summary['contactPoint'] ?? null,
                'keywords' => $this->ensureArray($summary['keywords'] ?? null),
                'controlled_keywords' => $this->ensureArray($summary['controlledKeywords'] ?? null),
                'dataset_type' => $this->ensureArray($summary['datasetType'] ?? null),
                'dataset_sub_type' => $this->ensureArray($summary['datasetSubType'] ?? null),
                'in_pipeline' => $summary['inPipeline'] ?? null,
                'funders' => $this->ensureArray($summary['funders'] ?? null),
                'dataset_aliases' => $this->ensureArray($summary['datasetAliases'] ?? null),
                'description' => $summary['description'] ?? null,
                'doi_name' => $summary['doiName'] ?? null,
                'license_url' => $summary['licenseUrl'] ?? null,
                'landing_page' => $summary['landingPage'] ?? null,
                'creator_name' => $summary['creator']['name'] ?? null,
                'creator_ror_id' => $summary['creator']['rorId'] ?? null,
                'creator_orcid_id' => $summary['creator']['orcidId'] ?? null,
                'creator_gateway_id' => $summary['creator']['gatewayId'] ?? null,
                'theme' => $this->ensureArray($summary['theme'] ?? null),
                'publisher_name' => $summary['publisher']['name'] ?? null,
                'publisher_gateway_id' => $summary['publisher']['gatewayId'] ?? null,
                'publisher_ror_id' => $summary['publisher']['rorId'] ?? null,
                'population_size' => isset($summary['populationSize']) ? (int) $summary['populationSize'] : null,
            ]);

            Coverage::where('dataset_version_id', $dv->id)->delete();
            Coverage::create([
                'dataset_version_id' => $dv->id,
                'spatial' => $this->ensureArray($coverage['spatial'] ?? null),
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
                'origin_purpose' => $this->ensureArray($origin['purpose'] ?? null),
                'origin_source' => $this->ensureArray($origin['source'] ?? null),
                'origin_collection_situation' => $this->ensureArray($origin['collectionSituation'] ?? null),
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
                'is_generated_using' => $this->ensureArray($linkageMeta['isGeneratedUsing'] ?? null),
                'associated_media' => $this->ensureArray($linkageMeta['associatedMedia'] ?? null),
                'data_uses' => $this->ensureArray($linkageMeta['dataUses'] ?? null),
                'is_reference_in' => $this->ensureArray($linkageMeta['isReferenceIn'] ?? null),
                'tools' => $this->ensureArray($linkageMeta['tools'] ?? null),
                'investigations' => $this->ensureArray($linkageMeta['investigations'] ?? null),
                'synthetic_data_web_link' => $this->ensureArray($linkageMeta['syntheticDataWebLink'] ?? null),
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

            Required::where('dataset_version_id', $dv->id)->delete();
            if (! empty($required)) {
                Required::create([
                    'dataset_version_id' => $dv->id,
                    'gateway_id' => $required['gatewayId'] ?? null,
                    'gateway_pid' => $required['gatewayPid'] ?? null,
                    'issued' => $required['issued'] ?? null,
                    'modified' => $required['modified'] ?? null,
                    'version' => $required['version'] ?? null,
                    'revisions' => $required['revisions'] ?? null,
                ]);
            }

            StructuralTable::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $structuralMetadata as $tbl) {
                if (! is_array($tbl)) {
                    continue;
                }
                $tableRow = StructuralTable::create([
                    'dataset_version_id' => $dv->id,
                    'name' => $tbl['name'] ?? null,
                    'description' => $tbl['description'] ?? null,
                ]);
                foreach ((array) ($tbl['columns'] ?? []) as $col) {
                    if (! is_array($col)) {
                        continue;
                    }
                    $colRow = StructuralColumn::create([
                        'gwdm30_structural_table_id' => $tableRow->id,
                        'name' => $col['name'] ?? null,
                        'data_type' => $col['dataType'] ?? null,
                        'description' => $col['description'] ?? null,
                        'sensitive' => $col['sensitive'] ?? false,
                    ]);
                    foreach ((array) ($col['values'] ?? []) as $val) {
                        if (! is_array($val)) {
                            continue;
                        }
                        StructuralValue::create([
                            'gwdm30_structural_column_id' => $colRow->id,
                            'name' => $val['name'] ?? null,
                            'description' => $val['description'] ?? null,
                            'frequency' => isset($val['frequency']) ? (int) $val['frequency'] : null,
                        ]);
                    }
                }
            }

            TissueSampleCollection::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $tissuesSampleCollection as $tsc) {
                if (! is_array($tsc)) {
                    continue;
                }
                $tsm = $tsc['tissueSampleMetadata'] ?? [];
                $donor = $tsm['sampleDonor'] ?? [];
                TissueSampleCollection::create([
                    'dataset_version_id' => $dv->id,
                    'collection_id' => $tsc['id'] ?? null,
                    'data_categories' => $this->ensureArray($tsc['dataCategories'] ?? null),
                    'material_type' => $this->ensureArray($tsc['materialType'] ?? null),
                    'access_conditions' => $this->ensureArray($tsc['accessConditions'] ?? null),
                    'collection_type' => $this->ensureArray($tsc['collectionType'] ?? null),
                    'disease' => $this->ensureArray($tsc['disease'] ?? null),
                    'storage_temperature' => $this->ensureArray($tsc['storageTemperature'] ?? null),
                    'sample_age_range' => $this->ensureArray($tsc['sampleAgeRange'] ?? null),
                    'tsm_id' => $tsm['id'] ?? null,
                    'tsm_sample_type' => $this->ensureArray($tsm['sampleType'] ?? null),
                    'tsm_storage_temperature' => $tsm['storageTemperature'] ?? null,
                    'tsm_creation_date' => $tsm['creationDate'] ?? null,
                    'tsm_anatomical_site_ontology_code' => $this->ensureArray($tsm['anatomicalSiteOntologyCode'] ?? null),
                    'tsm_anatomical_site_ontology_description' => $this->ensureArray($tsm['anatomicalSiteOntologyDescription'] ?? null),
                    'tsm_anatomical_site_free_text' => $this->ensureArray($tsm['anatomicalSiteFreeText'] ?? null),
                    'tsm_sample_content_diagnosis' => $this->ensureArray($tsm['sampleContentDiagnosis'] ?? null),
                    'tsm_use_restrictions' => $this->ensureArray($tsm['useRestrictions'] ?? null),
                    'tsm_donor_id' => $donor['id'] ?? null,
                    'tsm_donor_sex' => $donor['sex'] ?? null,
                    'tsm_donor_birth_date' => $donor['birthDate'] ?? null,
                    'tsm_donor_data_categories' => $this->ensureArray($donor['dataCategories'] ?? null),
                ]);
            }

            ProjectGrant::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $projectGrants as $grant) {
                if (! is_array($grant)) {
                    continue;
                }
                ProjectGrant::create([
                    'dataset_version_id' => $dv->id,
                    'pid' => $grant['pid'] ?? null,
                    'project_grant_name' => $grant['projectGrantName'] ?? null,
                    'lead_researcher' => $grant['leadResearcher'] ?? null,
                    'lead_research_institute' => $grant['leadResearchInstitute'] ?? null,
                    'grant_number' => $grant['grantNumber'] ?? null,
                    'project_grant_start_date' => $grant['projectGrantStartDate'] ?? null,
                    'project_grant_end_date' => $grant['projectGrantEndDate'] ?? null,
                    'project_grant_scope' => $grant['projectGrantScope'] ?? null,
                ]);
            }

            DatasetFilter::where('dataset_version_id', $dv->id)->delete();
            foreach ((array) $datasetFilters as $filter) {
                if (! is_array($filter)) {
                    continue;
                }
                DatasetFilter::create([
                    'dataset_version_id' => $dv->id,
                    'filter_id' => $filter['id'] ?? null,
                    'label' => $filter['label'] ?? null,
                    'category' => $filter['category'] ?? null,
                    'primary_group' => $filter['primaryGroup'] ?? null,
                    'description' => $filter['description'] ?? null,
                ]);
            }

            Erd::where('dataset_version_id', $dv->id)->delete();
            if (! empty($erd)) {
                Erd::create([
                    'dataset_version_id' => $dv->id,
                    'description' => $erd['description'] ?? null,
                ]);
            }
        });
    }

    // ── Read path ─────────────────────────────────────────────────────────────

    public function preloadSections(DatasetVersion $dv): void
    {
        // Skip the ~16 section queries entirely when the denormalised cache will
        // serve this read (see afterRead()/extractStoredGwdm()).
        if ($this->hasCachedSections($dv)) {
            return;
        }

        $id = $dv->id;
        $dv->setRelation('gwdm30Summary',                 Summary::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30Coverage',                Coverage::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30Provenance',              Provenance::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30Accessibility',           Accessibility::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30LinkageMeta',             LinkageMeta::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30Omics',                   Omics::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30Erd',                     Erd::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30Required',                Required::where('dataset_version_id', $id)->first());
        $dv->setRelation('gwdm30Observations',            Observation::where('dataset_version_id', $id)->orderBy('id')->get());
        $dv->setRelation('gwdm30DemographicFrequencies',  DemographicFrequency::where('dataset_version_id', $id)->orderBy('id')->get());
        $dv->setRelation('gwdm30Distributions',           Distribution::where('dataset_version_id', $id)->orderBy('id')->get());
        $dv->setRelation('gwdm30QualityAnnotations',      QualityAnnotation::where('dataset_version_id', $id)->orderBy('id')->get());
        $dv->setRelation('gwdm30StructuralTables',        StructuralTable::where('dataset_version_id', $id)->with(['columns.values'])->orderBy('id')->get());
        $dv->setRelation('gwdm30TissueSampleCollections', TissueSampleCollection::where('dataset_version_id', $id)->orderBy('id')->get());
        $dv->setRelation('gwdm30ProjectGrants',           ProjectGrant::where('dataset_version_id', $id)->orderBy('id')->get());
        $dv->setRelation('gwdm30DatasetFilters',          DatasetFilter::where('dataset_version_id', $id)->orderBy('id')->get());
    }

    /**
     * Batch equivalent of preloadSections() for a whole collection of dataset
     * versions: loads each section type ONCE across all version ids and hydrates
     * every row's relations from the result. Fixes the N+1 (~16 queries per row)
     * when reading many 3.0 versions at once, e.g. GET /metadata-versions.
     *
     * @param  \Illuminate\Support\Collection<int, DatasetVersion>  $versions
     */
    public function preloadSectionsForVersions(Collection $versions): void
    {
        $ids = $versions->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        // Single-row sections: keyBy dataset_version_id -> at most one row each.
        $single = [
            'gwdm30Summary' => Summary::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
            'gwdm30Coverage' => Coverage::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
            'gwdm30Provenance' => Provenance::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
            'gwdm30Accessibility' => Accessibility::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
            'gwdm30LinkageMeta' => LinkageMeta::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
            'gwdm30Omics' => Omics::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
            'gwdm30Erd' => Erd::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
            'gwdm30Required' => Required::whereIn('dataset_version_id', $ids)->get()->keyBy('dataset_version_id'),
        ];

        // Multi-row sections: groupBy dataset_version_id -> a collection each.
        $many = [
            'gwdm30Observations' => Observation::whereIn('dataset_version_id', $ids)->orderBy('id')->get()->groupBy('dataset_version_id'),
            'gwdm30DemographicFrequencies' => DemographicFrequency::whereIn('dataset_version_id', $ids)->orderBy('id')->get()->groupBy('dataset_version_id'),
            'gwdm30Distributions' => Distribution::whereIn('dataset_version_id', $ids)->orderBy('id')->get()->groupBy('dataset_version_id'),
            'gwdm30QualityAnnotations' => QualityAnnotation::whereIn('dataset_version_id', $ids)->orderBy('id')->get()->groupBy('dataset_version_id'),
            'gwdm30StructuralTables' => StructuralTable::whereIn('dataset_version_id', $ids)->with(['columns.values'])->orderBy('id')->get()->groupBy('dataset_version_id'),
            'gwdm30TissueSampleCollections' => TissueSampleCollection::whereIn('dataset_version_id', $ids)->orderBy('id')->get()->groupBy('dataset_version_id'),
            'gwdm30ProjectGrants' => ProjectGrant::whereIn('dataset_version_id', $ids)->orderBy('id')->get()->groupBy('dataset_version_id'),
            'gwdm30DatasetFilters' => DatasetFilter::whereIn('dataset_version_id', $ids)->orderBy('id')->get()->groupBy('dataset_version_id'),
        ];

        foreach ($versions as $dv) {
            foreach ($single as $relation => $keyed) {
                $dv->setRelation($relation, $keyed->get($dv->id));
            }
            foreach ($many as $relation => $grouped) {
                $dv->setRelation($relation, $grouped->get($dv->id, collect())->values());
            }
        }
    }

    private function read(DatasetVersion $dv): array
    {
        return array_merge(
            $this->readSections($dv),
            $this->readLinkageOnly($dv),
        );
    }

    /**
     * The 15 non-linkage GWDM sections, reassembled from the SQL tables. This is
     * exactly what gets denormalised into the metadata JSON column on write
     * (see refreshSectionCache()), so cache and SQL reconstruction stay identical.
     */
    private function readSections(DatasetVersion $dv): array
    {
        return array_merge(
            $this->readRequired($dv),
            $this->readSummary($dv),
            $this->readCoverage($dv),
            $this->readProvenance($dv),
            $this->readAccessibility($dv),
            $this->readObservations($dv),
            $this->readOmics($dv),
            $this->readDemographicFrequency($dv),
            $this->readDistributions($dv),
            $this->readQualityAnnotations($dv),
            $this->readStructuralMetadata($dv),
            $this->readTissuesSampleCollection($dv),
            $this->readProjectGrants($dv),
            $this->readDatasetFilters($dv),
            $this->readErd($dv),
        );
    }

    /**
     * The linkage section only, always read fresh from the junction tables so
     * resolved link titles track each target dataset's current latest version
     * (deliberately excluded from the section cache).
     *
     * @return array{linkage?: array} empty when there is no linkage at all
     */
    private function readLinkageOnly(DatasetVersion $dv): array
    {
        $linkageSection = $this->readLinkages($dv);
        $linkageMetaSection = $this->readLinkageMeta($dv);

        if (empty($linkageSection) && empty($linkageMetaSection)) {
            return [];
        }

        return [
            'linkage' => array_merge(
                $linkageSection['linkage'] ?? [],
                $linkageMetaSection['linkage'] ?? [],
            ),
        ];
    }

    /**
     * Whether the caller opted into the denormalised metadata-column cache via the
     * per-request `X-Gwdm-Cache: bypass` header — "bypass" = bypass the default
     * SQL reconstruction and serve the cached sections instead.
     *
     * With no header, reads reconstruct from the SQL section tables (the
     * authoritative source of truth).
     */
    private function cacheRequested(): bool
    {
        return request()->header('X-Gwdm-Cache') === 'bypass';
    }

    /**
     * True when the caller opted into the cache AND this row carries a non-empty
     * denormalised 'metadata' section blob to serve it from. Otherwise the read
     * falls through to the SQL reconstruction.
     */
    private function hasCachedSections(DatasetVersion $dv): bool
    {
        if (! $this->cacheRequested()) {
            return false;
        }

        $stored = $dv->metadata;

        return is_array($stored) && ! empty($stored['metadata']);
    }

    private function readSummary(DatasetVersion $dv): array
    {
        $row = $dv->relationLoaded('gwdm30Summary') ? $dv->gwdm30Summary : Summary::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }
        return [
            'summary' => [
                'title'      => $dv->title,
                'shortTitle' => $dv->short_title,
                'abstract' => $row->abstract,
                'contactPoint' => $row->contact_point,
                'keywords' => $row->keywords,
                'controlledKeywords' => $row->controlled_keywords,
                'datasetAliases' => $row->dataset_aliases,
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
        $row = $dv->relationLoaded('gwdm30Coverage') ? $dv->gwdm30Coverage : Coverage::where('dataset_version_id', $dv->id)->first();
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
        $row = $dv->relationLoaded('gwdm30Provenance') ? $dv->gwdm30Provenance : Provenance::where('dataset_version_id', $dv->id)->first();
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
        $row = $dv->relationLoaded('gwdm30Accessibility') ? $dv->gwdm30Accessibility : Accessibility::where('dataset_version_id', $dv->id)->first();
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
                    'resourceCreator' => [
                        'name' => $row->resource_creator,
                        'gatewayId' => $row->resource_creator_gateway_id,
                        'rorId' => $row->resource_creator_ror_id,
                    ],
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
        $rows = $dv->relationLoaded('gwdm30Observations') ? $dv->gwdm30Observations : Observation::where('dataset_version_id', $dv->id)->orderBy('id')->get();
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
        $row = $dv->relationLoaded('gwdm30LinkageMeta') ? $dv->gwdm30LinkageMeta : LinkageMeta::where('dataset_version_id', $dv->id)->first();
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
        $row = $dv->relationLoaded('gwdm30Omics') ? $dv->gwdm30Omics : Omics::where('dataset_version_id', $dv->id)->first();
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
        $rows = $dv->relationLoaded('gwdm30DemographicFrequencies') ? $dv->gwdm30DemographicFrequencies : DemographicFrequency::where('dataset_version_id', $dv->id)->orderBy('id')->get();

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
        $rows = $dv->relationLoaded('gwdm30Distributions') ? $dv->gwdm30Distributions : Distribution::where('dataset_version_id', $dv->id)->orderBy('id')->get();
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
        $rows = $dv->relationLoaded('gwdm30QualityAnnotations') ? $dv->gwdm30QualityAnnotations : QualityAnnotation::where('dataset_version_id', $dv->id)->orderBy('id')->get();
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
            ->get();

        $publications = PublicationHasDatasetVersion::query()
            ->where('publication_has_dataset_version.dataset_version_id', $dv->id)
            ->where('publication_has_dataset_version.description', self::LINKAGE_DESCRIPTION)
            ->leftJoin('publications', 'publications.id', '=', 'publication_has_dataset_version.publication_id')
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

    private function readRequired(DatasetVersion $dv): array
    {
        $row = $dv->relationLoaded('gwdm30Required') ? $dv->gwdm30Required : Required::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'required' => [
                'gatewayId' => $row->gateway_id,
                'gatewayPid' => $row->gateway_pid,
                'issued' => $row->issued?->toIso8601String(),
                'modified' => $row->modified?->toIso8601String(),
                'version' => $row->version,
                'revisions' => $row->revisions ?? [],
            ],
        ];
    }

    private function readStructuralMetadata(DatasetVersion $dv): array
    {
        $tables = $dv->relationLoaded('gwdm30StructuralTables')
            ? $dv->gwdm30StructuralTables
            : StructuralTable::where('dataset_version_id', $dv->id)->with(['columns.values'])->orderBy('id')->get();

        if ($tables->isEmpty()) {
            return [];
        }

        return [
            'structuralMetadata' => $tables->map(fn (StructuralTable $tbl) => [
                'name' => $tbl->name,
                'description' => $tbl->description,
                'columns' => $tbl->columns->map(fn (StructuralColumn $col) => [
                    'name' => $col->name,
                    'dataType' => $col->data_type,
                    'description' => $col->description,
                    'sensitive' => $col->sensitive,
                    'values' => $col->values->map(fn (StructuralValue $val) => [
                        'name' => $val->name,
                        'description' => $val->description,
                        'frequency' => $val->frequency,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function readTissuesSampleCollection(DatasetVersion $dv): array
    {
        $rows = $dv->relationLoaded('gwdm30TissueSampleCollections') ? $dv->gwdm30TissueSampleCollections : TissueSampleCollection::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'tissuesSampleCollection' => $rows->map(fn ($r) => [
                'id' => $r->collection_id,
                'dataCategories' => $r->data_categories,
                'materialType' => $r->material_type,
                'accessConditions' => $r->access_conditions,
                'collectionType' => $r->collection_type,
                'disease' => $r->disease,
                'storageTemperature' => $r->storage_temperature,
                'sampleAgeRange' => $r->sample_age_range,
                'tissueSampleMetadata' => [
                    'id' => $r->tsm_id,
                    'sampleType' => $r->tsm_sample_type,
                    'storageTemperature' => $r->tsm_storage_temperature,
                    'creationDate' => $r->tsm_creation_date?->toDateString(),
                    'anatomicalSiteOntologyCode' => $r->tsm_anatomical_site_ontology_code,
                    'anatomicalSiteOntologyDescription' => $r->tsm_anatomical_site_ontology_description,
                    'anatomicalSiteFreeText' => $r->tsm_anatomical_site_free_text,
                    'sampleContentDiagnosis' => $r->tsm_sample_content_diagnosis,
                    'useRestrictions' => $r->tsm_use_restrictions,
                    'sampleDonor' => [
                        'id' => $r->tsm_donor_id,
                        'sex' => $r->tsm_donor_sex,
                        'birthDate' => $r->tsm_donor_birth_date?->toDateString(),
                        'dataCategories' => $r->tsm_donor_data_categories,
                    ],
                ],
            ])->values()->all(),
        ];
    }

    private function readProjectGrants(DatasetVersion $dv): array
    {
        $rows = $dv->relationLoaded('gwdm30ProjectGrants') ? $dv->gwdm30ProjectGrants : ProjectGrant::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'projectGrants' => $rows->map(fn ($r) => [
                'pid' => $r->pid,
                'projectGrantName' => $r->project_grant_name,
                'leadResearcher' => $r->lead_researcher,
                'leadResearchInstitute' => $r->lead_research_institute,
                'grantNumber' => $r->grant_number,
                'projectGrantStartDate' => $r->project_grant_start_date,
                'projectGrantEndDate' => $r->project_grant_end_date,
                'projectGrantScope' => $r->project_grant_scope,
            ])->values()->all(),
        ];
    }

    private function readDatasetFilters(DatasetVersion $dv): array
    {
        $rows = $dv->relationLoaded('gwdm30DatasetFilters') ? $dv->gwdm30DatasetFilters : DatasetFilter::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'datasetFilters' => $rows->map(fn ($r) => [
                'id' => $r->filter_id,
                'label' => $r->label,
                'category' => $r->category,
                'primaryGroup' => $r->primary_group,
                'description' => $r->description,
            ])->values()->all(),
        ];
    }

    private function readErd(DatasetVersion $dv): array
    {
        $row = $dv->relationLoaded('gwdm30Erd') ? $dv->gwdm30Erd : Erd::where('dataset_version_id', $dv->id)->first();
        if (! $row) {
            return [];
        }

        return [
            'erd' => [
                'description' => $row->description,
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
