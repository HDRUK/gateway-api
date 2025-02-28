<?php

namespace App\Http\Traits;

use Auditor;
use Exception;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DurHasPublication;
use App\Models\PublicationHasTool;
use App\Models\PublicationHasDatasetVersion;

trait PublicationsV2Helper
{
    public function getPublicationById(int $publicationId)
    {

        $publication = Publication::with(['tools', 'durs', 'collections'])
            ->where(['id' => $publicationId])
            ->first();

        $publication->setAttribute('datasets', $publication->allDatasets);

        return $publication;
    }

    // datasets
    public function checkDatasets(int $publicationId, array $inDatasets)
    {

        // There was an error here where by the Publications were not getting cleared out / exhibiting
        // weird cache like behavior on the FE. Clearing all links for a Pub and restoring active ones
        // is much cleaner approach.

        $this->deletePublicationHasDatasetVersions($publicationId);

        foreach ($inDatasets as $dataset) {
            $datasetVersionId = Dataset::where('id', (int) $dataset['id'])->first()->latestVersion()->id;
            $checking = $this->checkInPublicationHasDatasetVersions($publicationId, $datasetVersionId, $dataset);

            if (!$checking) {
                $this->addPublicationHasDatasetVersion($publicationId, $dataset, $datasetVersionId);
            }
        }
    }

    public function addPublicationHasDatasetVersion(int $publicationId, array $dataset, int $datasetVersionId)
    {
        try {
            $arrCreate = [
                'publication_id' => $publicationId,
                'dataset_version_id' => $datasetVersionId,
                'link_type' => $dataset['link_type'] ?? 'USING', // Assuming default link_type is 'USING'
            ];

            if (array_key_exists('updated_at', $dataset)) { // special for migration
                $arrCreate['created_at'] = $dataset['updated_at'];
                $arrCreate['updated_at'] = $dataset['updated_at'];
            }

            $linkage = PublicationHasDatasetVersion::withTrashed()->firstOrCreate($arrCreate);
            // Restore if it’s soft-deleted
            if ($linkage->trashed()) {
                $linkage->restore();
            }

            return $linkage;

        } catch (Exception $e) {
            throw new Exception("addPublicationHasDatasetVersion :: " . $e->getMessage());
        }
    }

    public function checkInPublicationHasDatasetVersions(int $publicationId, int $datasetVersionId, array $dataset)
    {
        try {
            return PublicationHasDatasetVersion::where([
                'publication_id' => $publicationId,
                'dataset_version_id' => $datasetVersionId,
                'link_type' => $dataset['link_type'] ?? 'USING',
                'description' => null,
            ])->first();
        } catch (Exception $e) {
            throw new Exception("checkInPublicationHasDatasetVersions :: " . $e->getMessage());
        }
    }

    public function deletePublicationHasDatasetVersions(int $publicationId)
    {
        try {
            return PublicationHasDatasetVersion::where([
                'publication_id' => $publicationId,
            ])->forceDelete();
        } catch (Exception $e) {
            throw new Exception("deletePublicationHasDatasetVersions :: " . $e->getMessage());
        }
    }

    // tools
    public function checkTools(int $publicationId, array $inTools, int $userId = null)
    {
        $pubs = PublicationHasTool::where(['publication_id' => $publicationId])->get();
        foreach ($pubs as $pub) {
            if (!in_array($pub->tool_id, $this->extractInputIdToArray($inTools))) {
                $this->deletePublicationHasTools($publicationId, $pub->tool_id);
            }
        }

        foreach ($inTools as $tool) {
            $checking = $this->checkInPublicationHasTools($publicationId, (int)$tool['id']);

            if (!$checking) {
                $this->addPublicationHasTool($publicationId, $tool, $userId);
            }
        }
    }

    public function addPublicationHasTool(int $publicationId, array $tool, int $userId = null)
    {
        try {
            $arrCreate = [
                'publication_id' => $publicationId,
                'tool_id' => $tool['id'],
            ];

            if (array_key_exists('user_id', $tool)) {
                $arrCreate['user_id'] = (int)$tool['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('updated_at', $tool)) { // special for migration
                $arrCreate['created_at'] = $tool['updated_at'];
                $arrCreate['updated_at'] = $tool['updated_at'];
            }

            return PublicationHasTool::updateOrCreate(
                $arrCreate,
                [
                    'publication_id' => $publicationId,
                    'tool_id' => $tool['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception("addPublicationHasTool :: " . $e->getMessage());
        }
    }

    public function checkInPublicationHasTools(int $publicationId, int $toolId)
    {
        try {
            return PublicationHasTool::where([
                'publication_id' => $publicationId,
                'tool_id' => $toolId,
            ])->first();
        } catch (Exception $e) {
            throw new Exception("checkInPublicationHasTools :: " . $e->getMessage());
        }
    }

    public function deletePublicationHasTools(int $publicationId, int $toolId)
    {
        try {
            return PublicationHasTool::where([
                'publication_id' => $publicationId,
                'tool_id' => $toolId,
            ])->forceDelete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deletePublicationHasTools :: ' . $e->getMessage());
        }
    }

    // DURs
    public function checkDurs(int $publicationId, array $inDurs, int $userId = null)
    {
        $durs = DurHasPublication::where(['publication_id' => $publicationId])->get();
        foreach ($durs as $d) {
            if (!in_array($d->dur_id, $this->extractInputIdToArray($inDurs))) {
                $this->deleteDurHasPublications($d->dur_id, $publicationId);
            }
        }

        foreach ($inDurs as $dur) {
            $checking = $this->checkInDurHasPublications((int)$dur['id'], $publicationId);

            if (!$checking) {
                $this->addDurHasPublication($dur, $publicationId, $userId);
            }
        }
    }

    public function addDurHasPublication(array $dur, int $publicationId, int $userId = null)
    {
        try {
            $arrCreate = [
                'dur_id' => $dur['id'],
                'publication_id' => $publicationId,
            ];

            if (array_key_exists('user_id', $dur)) {
                $arrCreate['user_id'] = (int)$dur['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $dur)) {
                $arrCreate['reason'] = $dur['reason'];
            }

            return DurHasPublication::updateOrCreate(
                $arrCreate,
                [
                    'dur_id' => $dur['id'],
                    'publication_id' => $publicationId,
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addDurHasPublication :: ' . $e->getMessage());
        }
    }

    public function checkInDurHasPublications(int $durId, int $publicationId)
    {
        try {
            return DurHasPublication::where([
                'dur_id' => $durId,
                'publication_id' => $publicationId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInDurHasPublications :: ' . $e->getMessage());
        }
    }

    public function deleteDurHasPublications(int $durId, int $publicationId)
    {
        try {
            return DurHasPublication::where([
                'dur_id' => $durId,
                'publication_id' => $publicationId,
            ])->forceDelete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteDurHasPublications :: ' . $e->getMessage());
        }
    }

    public function extractInputIdToArray(array $input): array
    {
        $response = [];
        foreach ($input as $value) {
            $response[] = $value['id'];
        }

        return $response;
    }
}
