<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Team;

/**
 * Handler for GWDM >= 1.1, covering versions 2.0 and 2.1.
 *
 * Differences from 1.x:
 *   - summary.publisher uses the NEW format: { gatewayId, name }
 *   - required block includes a 'version' field ("X.0.0")
 *
 * 2.0 and 2.1 share the same JSON blob storage strategy — both use
 * the metadata column with RFC 6902 delta compression. Schema-level
 * differences (new/changed GWDM fields in 2.1) are handled by TRASER,
 * not by this handler.
 */
class Gwdm2xHandler extends GwdmMetadataHandler
{
    public function buildPublisher(Team $team): array
    {
        return [
            'gatewayId' => $team->pid,
            'name'      => $team->name,
        ];
    }

    public function buildRequiredBlock(Dataset $dataset, int $versionNumber): array
    {
        return [
            'gatewayId'  => strval($dataset->id),
            'gatewayPid' => $dataset->pid,
            'issued'     => $dataset->created,
            'modified'   => $dataset->updated,
            'version'    => $this->formatVersion($versionNumber),
            'revisions'  => $this->buildRevisions($dataset, $versionNumber),
        ];
    }

    public function prepareMetadata(array $gwdm, Dataset $dataset, Team $team, int $versionNumber): array
    {
        $required = $this->buildRequiredBlock($dataset, $versionNumber);

        // Preserve any version string the caller (or TRASER) already set,
        // falling back to the computed one from the DB version number.
        // DB-derived values win for all other required keys.
        $required['version'] = $gwdm['required']['version'] ?? $required['version'];

        $gwdm['required']             = array_merge($gwdm['required'] ?? [], $required);
        $gwdm['summary']['publisher'] = $this->buildPublisher($team);

        return $gwdm;
    }

    // ── Linkage extraction ────────────────────────────────────────────────────

    private const LINKAGE_DESCRIPTION = 'Extracted from GWDM';

    public function extractLinkages(DatasetVersion $dv): void
    {
        $linkage = $dv->metadata['metadata']['linkage'] ?? [];

        $datasetLinkages = $linkage['datasetLinkage'] ?? null;
        $datasetLinkages = $datasetLinkages !== '' ? $datasetLinkages : null;

        $aboutLinkages = $linkage['publicationAboutDataset'] ?? null;
        $aboutLinkages = $aboutLinkages !== '' ? $aboutLinkages : null;

        $usingLinkages = $linkage['publicationUsingDataset'] ?? null;
        $usingLinkages = $usingLinkages !== '' ? $usingLinkages : null;

        $this->processDatasetLinkages($dv->id, $datasetLinkages);
        $this->processPublicationLinkages($dv->id, $aboutLinkages, 'ABOUT');
        $this->processPublicationLinkages($dv->id, $usingLinkages, 'USING');
    }

    protected function processDatasetLinkages(int $sourceVersionId, ?array $datasetLinkages): void
    {
        DatasetVersionHasDatasetVersion::where([
            'dataset_version_source_id' => $sourceVersionId,
            'direct_linkage'            => 1,
            'description'               => self::LINKAGE_DESCRIPTION,
        ])->delete();

        if (is_null($datasetLinkages)) {
            return;
        }

        foreach ($datasetLinkages as $key => $data) {
            if (!$data) {
                continue;
            }
            foreach ($data as $d) {
                $targetVersionId = $this->findTargetDataset($d);
                if (!$targetVersionId) {
                    continue;
                }
                DatasetVersionHasDatasetVersion::firstOrCreate([
                    'dataset_version_source_id' => $sourceVersionId,
                    'dataset_version_target_id' => $targetVersionId,
                    'linkage_type'              => $key,
                    'direct_linkage'            => 1,
                    'description'               => self::LINKAGE_DESCRIPTION,
                ]);
            }
        }
    }

    protected function processPublicationLinkages(int $sourceVersionId, ?array $publicationLinkages, string $linkType): void
    {
        PublicationHasDatasetVersion::where([
            'dataset_version_id' => $sourceVersionId,
            'description'        => self::LINKAGE_DESCRIPTION,
            'link_type'          => $linkType,
        ])->delete();

        if (is_null($publicationLinkages)) {
            return;
        }

        foreach ($publicationLinkages as $doi) {
            if (!$doi) {
                continue;
            }

            $publicationId = $this->findTargetPublication($doi);
            if (!$publicationId) {
                continue;
            }

            $linkage = PublicationHasDatasetVersion::withTrashed()->firstOrCreate([
                'publication_id'     => $publicationId,
                'dataset_version_id' => $sourceVersionId,
                'link_type'          => $linkType,
                'description'        => self::LINKAGE_DESCRIPTION,
            ]);

            if ($linkage->trashed()) {
                $linkage->restore();
            }
        }
    }

    protected function findTargetDataset(array $data): ?int
    {
        $id    = $data['url'] ?? null;
        $pid   = $data['pid'] ?? null;
        $title = $data['title'] ?? null;

        if ($id) {
            $urlParts = explode('/', parse_url($id, PHP_URL_PATH));
            $id = end($urlParts);
            $dataset = Dataset::find($id);
            if ($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if ($pid) {
            $dataset = Dataset::where('pid', $pid)->first();
            if ($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if ($title) {
            $datasetVersion = DatasetVersion::filterTitle($title)->first();
            if ($datasetVersion) {
                return $datasetVersion->id;
            }
        }

        return null;
    }

    protected function findTargetPublication(string $doi): ?int
    {
        $publication = Publication::whereRaw(
            "REPLACE(REPLACE(paper_doi, 'https://doi.org/', ''), 'doi.org/', '') = ?",
            [$doi]
        )->first();

        return $publication?->id;
    }
}
