<?php

namespace App\Http\Traits;

use Auditor;
use Exception;
use App\Models\Dur;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\Collection;
use App\Models\DurHasTool;
use App\Models\ToolHasTag;
use App\Http\Enums\TagType;
use App\Models\Publication;
use App\Models\DatasetVersion;
use App\Models\CollectionHasTool;
use App\Models\PublicationHasTool;
use App\Models\ToolHasTypeCategory;
use App\Models\DatasetVersionHasTool;
use App\Exceptions\NotFoundException;
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasProgrammingLanguage;

trait ToolsV2Helper
{
    public function getToolById(int $toolId, ?int $teamId = null, ?int $userId = null, ?bool $onlyActive = false, ?bool $onlyActiveRelated = false, ?bool $trimmed = false)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications' => function ($query) use ($onlyActiveRelated) {
                if ($onlyActiveRelated) {
                    $query->where('status', Publication::STATUS_ACTIVE);
                }
            },
            'durs' => function ($query) use ($onlyActiveRelated) {
                if ($onlyActiveRelated) {
                    $query->where('status', Dur::STATUS_ACTIVE);
                }
            },
            'collections' => function ($query) use ($onlyActiveRelated) {
                if ($onlyActiveRelated) {
                    $query->where('status', Collection::STATUS_ACTIVE);
                }
            },
            'category',
            'versions' => function ($query) use ($trimmed) {
                $query->whereHas('dataset', fn ($q) => $q->where('status', 'ACTIVE'));
                $query->when($trimmed, function ($q) {
                    $q->selectRaw('
                        dataset_versions.id,dataset_versions.dataset_id,
                        short_title as shortTitle,
                        CONVERT(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.populationSize")), SIGNED) as populationSize,
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.datasetType")) as datasetType,
                        JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.publisher.name")) as dataCustodian
                    ');
                });
            },
        ])
        ->where(['id' => $toolId])
        ->when($teamId, function ($query) use ($teamId) {
            return $query->where(['team_id' => $teamId]);
        })
        ->when($userId, function ($query) use ($userId) {
            return $query->where(['user_id' => $userId]);
        })
        ->when($onlyActive, function ($query) {
            return $query->where(['status' => Tool::STATUS_ACTIVE]);
        })
        ->first();
        if (!$tool) {
            throw new NotFoundException();
        }

        $tool->name = html_entity_decode($tool->name);
        $tool->description = html_entity_decode($tool->description);
        $tool->results_insights = html_entity_decode($tool->results_insights);
        $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
        return $tool;
    }

    /**
     * Creates a new tag if it doesn't exist.
     *
     * @param mixed $value
     * @return mixed
     */
    public function createNewTagIfNotExists(mixed $value)
    {
        if (!is_numeric($value) && !empty($value)) {
            $tag = Tag::where([
                'description' => $value,
            ])->first();

            if (is_null($tag)) {
                $createdTag = Tag::create([
                    'type' => TagType::TOPICS,
                    'description' => $value,
                    'enabled' => true,
                ]);

                return $createdTag->id;
            } else {
                return $tag->id;
            }
        }

        return null;
    }

    /**
     * Insert data into ToolHasTag
     *
     * @param array $tags
     * @param integer $toolId
     * @return mixed
     */
    public function insertToolHasTag(array $tags, int $toolId): mixed
    {
        try {
            foreach ($tags as $value) {
                if ($value === 0) {
                    continue;
                }

                $createdTagId = $this->createNewTagIfNotExists($value);

                // This whole thing could be an updateOrCreate, but Eloquent can't cope with the fact
                // this model has no single primary key column so we have to go around the houses.

                if (is_null($createdTagId)) {
                    $toolHasTag = ToolHasTag::where(
                        [
                        'tool_id' => (int)$toolId,
                        'tag_id' => (int)$value,
                        ]
                    )->first();

                    // undelete if it has been soft-deleted
                    if ($toolHasTag && $toolHasTag->deleted_at != null) {
                        // We have to use a raw query to undelete because Eloquent can't cope with the fact this model has no single primary key column.
                        \DB::table('tool_has_tags')
                        ->where(['tool_id' => (int)$toolId,
                            'tag_id' => (int)$value])
                            ->update(['deleted_at' => null]);
                    };
                } else {
                    $toolHasTag = false;
                }

                // create it if required
                if (!$toolHasTag) {
                    ToolHasTag::create([
                        'tool_id' => (int)$toolId,
                        'tag_id' => $createdTagId ? $createdTagId : (int)$value,
                    ]);
                }
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into DatasetVersionHasTool
     *
     * @param array $datasetIds
     * @param integer $toolId
     * @return mixed
     */
    public function insertDatasetVersionHasTool(array $datasetIds, int $toolId): mixed
    {
        try {
            foreach ($datasetIds as $value) {
                if (is_array($value)) {
                    $datasetVersionIDs = DatasetVersion::where('dataset_id', $value['id'])->pluck('id')->all();

                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        DatasetVersionHasTool::updateOrCreate([
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                            'link_type' => $value['link_type'],
                            'deleted_at' => null,
                        ]);
                    }
                } else {
                    $datasetVersionIDs = DatasetVersion::where('dataset_id', $value)->pluck('id')->all();

                    foreach ($datasetVersionIDs as $datasetVersionID) {
                        DatasetVersionHasTool::updateOrCreate([
                            'tool_id' => $toolId,
                            'dataset_version_id' => $datasetVersionID,
                            'deleted_at' => null,
                        ]);
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into ToolHasProgrammingLanguage
     *
     * @param array $programmingLanguages
     * @param integer $toolId
     * @return mixed
     */
    public function insertToolHasProgrammingLanguage(array $programmingLanguages, int $toolId): mixed
    {
        try {
            foreach ($programmingLanguages as $value) {
                ToolHasProgrammingLanguage::updateOrCreate(
                    [
                        'tool_id' => (int)$toolId,
                        'programming_language_id' => (int)$value,
                    ],
                    ['deleted_at' => null]
                );
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into ToolHasProgrammingPackage
     *
     * @param array $programmingPackages
     * @param integer $toolId
     * @return mixed
     */
    public function insertToolHasProgrammingPackage(array $programmingPackages, int $toolId): mixed
    {
        try {
            foreach ($programmingPackages as $value) {
                ToolHasProgrammingPackage::updateOrCreate(
                    [
                        'tool_id' => (int)$toolId,
                        'programming_package_id' => (int)$value,
                    ],
                    ['deleted_at' => null]
                );
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into ToolHasTypeCategory
     *
     * @param array $typeCategories
     * @param integer $toolId
     * @return mixed
     */
    public function insertToolHasTypeCategory(array $typeCategories, int $toolId): mixed
    {
        try {
            foreach ($typeCategories as $value) {
                ToolHasTypeCategory::updateOrCreate(
                    [
                        'tool_id' => (int)$toolId,
                        'type_category_id' => (int)$value,
                    ],
                    ['deleted_at' => null]
                );
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data into DurHasTool
     *
     * @param array $durs
     * @param integer $toolId
     * @return mixed
     */
    public function insertDurHasTool(array $durs, int $toolId): mixed
    {
        try {
            $durHasTools = DurHasTool::where(['tool_id' => $toolId])->get();
            foreach ($durHasTools as $durHasTool) {
                if (!in_array($durHasTool->dur_id, $durs)) {
                    DurHasTool::where([
                        'tool_id' => (int)$toolId,
                        'dur_id' => (int)$durHasTool->dur_id,
                    ])->delete();
                } else {
                    DurHasTool::updateOrCreate([
                        'tool_id' => (int)$toolId,
                        'dur_id' => (int)$durHasTool->dur_id,
                    ]);
                }
            }
            foreach ($durs as $value) {
                DurHasTool::updateOrCreate([
                    'tool_id' => (int)$toolId,
                    'dur_id' => (int)$value,
                ]);
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // publications
    public function checkPublications(int $toolId, array $inPublications, ?int $userId = null)
    {
        $pubs = PublicationHasTool::where(['tool_id' => $toolId])->get();
        foreach ($pubs as $pub) {
            if (!in_array($pub->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deletePublicationHasTools($toolId, $pub->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInPublicationHasTools($toolId, (int)$publication['id']);

            if (!$checking) {
                $this->addPublicationHasTool($toolId, $publication, $userId);
            }
        }
    }

    public function addPublicationHasTool(int $toolId, array $publication, ?int $userId = null)
    {
        try {
            $arrCreate = [
                'tool_id' => $toolId,
                'publication_id' => $publication['id'],
            ];

            if (array_key_exists('user_id', $publication)) {
                $arrCreate['user_id'] = (int)$publication['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $publication)) {
                $arrCreate['reason'] = $publication['reason'];
            }

            if (array_key_exists('updated_at', $publication)) { // special for migration
                $arrCreate['created_at'] = $publication['updated_at'];
                $arrCreate['updated_at'] = $publication['updated_at'];
            }

            return PublicationHasTool::updateOrCreate(
                $arrCreate,
                [
                    'tool_id' => $toolId,
                    'publication_id' => $publication['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addPublicationHasTool :: ' . $e->getMessage());
        }
    }

    public function checkInPublicationHasTools(int $toolId, int $publicationId)
    {
        try {
            return PublicationHasTool::where([
                'tool_id' => $toolId,
                'publication_id' => $publicationId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInPublicationHasTools :: ' . $e->getMessage());
        }
    }

    public function deletePublicationHasTools(int $toolId, int $publicationId)
    {
        try {
            return PublicationHasTool::where([
                'tool_id' => $toolId,
                'publication_id' => $publicationId,
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

    // collections
    public function checkCollections(int $toolId, array $inCollections, ?int $userId = null)
    {
        $collectionHasTools = CollectionHasTool::where(['tool_id' => $toolId])->get();
        foreach ($collectionHasTools as $collectionHasTool) {
            if (!in_array($collectionHasTool->collection_id, $this->extractInputIdToArray($inCollections))) {
                $this->deleteCollectionHasTools($toolId, $collectionHasTool->collection_id);
            }
        }

        foreach ($inCollections as $collection) {
            $checking = $this->checkInCollectionHasTools($toolId, (int) $collection['id']);

            if (!$checking) {
                $this->addCollectionHasTool($toolId, $collection, $userId);
            }
        }
    }

    public function addCollectionHasTool(int $toolId, array $collection, ?int $userId = null)
    {
        try {
            $arrCreate = [
                'tool_id' => $toolId,
                'collection_id' => $collection['id'],
            ];

            if (array_key_exists('user_id', $collection)) {
                $arrCreate['user_id'] = (int)$collection['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $collection)) {
                $arrCreate['reason'] = $collection['reason'];
            }

            if (array_key_exists('updated_at', $collection)) { // special for migration
                $arrCreate['created_at'] = $collection['updated_at'];
                $arrCreate['updated_at'] = $collection['updated_at'];
            }

            return CollectionHasTool::updateOrCreate(
                $arrCreate,
                [
                    'tool_id' => $toolId,
                    'collection_id' => $collection['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasTool :: ' . $e->getMessage());
        }
    }

    public function checkInCollectionHasTools(int $toolId, int $collectionId)
    {
        try {
            return CollectionHasTool::where([
                'tool_id' => $toolId,
                'collection_id' => $collectionId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollecionHasTools :: ' . $e->getMessage());
        }
    }

    public function deleteCollectionHasTools(int $toolId, int $collectionId)
    {
        try {
            return CollectionHasTool::where([
                'tool_id' => $toolId,
                'collection_id' => $collectionId,
            ])->forceDelete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasTools :: ' . $e->getMessage());
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
