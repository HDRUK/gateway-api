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
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasProgrammingLanguage;

trait ToolsV2Helper
{
    public function getToolByUserIdAndByIdByStatus(int $userId, int $toolId, string $status)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications' => function ($query) use ($status) {
                $query->where('status', strtoupper($status));
            },
            'durs' => function ($query) use ($status) {
                $query->where('status', strtoupper($status));
            },
            'collections' => function ($query) use ($status) {
                $query->where('status', strtoupper($status));
            },
            'category',
        ])
        ->where([
            'user_id' => $userId,
            'id' => $toolId,
            'status' => strtoupper($status),
        ])
        ->first();

        if (is_null($tool)) {
            return null;
        }

        $tool->name = html_entity_decode($tool->name);
        $tool->description = html_entity_decode($tool->description);
        $tool->results_insights = html_entity_decode($tool->results_insights);
        $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
        return $tool;
    }

    public function getToolByTeamIdAndByIdByStatus(int $teamId, int $toolId, string $status)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications' => function ($query) use ($status) {
                $query->where('status', strtoupper($status));
            },
            'durs' => function ($query) use ($status) {
                $query->where('status', strtoupper($status));
            },
            'collections' => function ($query) use ($status) {
                $query->where('status', strtoupper($status));
            },
            'category',
        ])
        ->where([
            'team_id' => $teamId,
            'id' => $toolId,
            'status' => strtoupper($status),
        ])
        ->first();

        if (is_null($tool)) {
            return null;
        }

        $tool->name = html_entity_decode($tool->name);
        $tool->description = html_entity_decode($tool->description);
        $tool->results_insights = html_entity_decode($tool->results_insights);
        $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
        return $tool;
    }

    public function getToolByUserIdAndById(int $userId, int $toolId, bool $onlyActive = false)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Publication::STATUS_ACTIVE);
                }
            },
            'durs' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Dur::STATUS_ACTIVE);
                }
            },
            'collections' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Collection::STATUS_ACTIVE);
                }
            },
            'category',
        ])
        ->where([
            'user_id' => $userId,
            'id' => $toolId,
            ])
        ->first();

        $tool->name = html_entity_decode($tool->name);
        $tool->description = html_entity_decode($tool->description);
        $tool->results_insights = html_entity_decode($tool->results_insights);
        $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
        return $tool;
    }

    public function getToolByTeamIdAndById(int $teamId, int $toolId, bool $onlyActive = false)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Publication::STATUS_ACTIVE);
                }
            },
            'durs' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Dur::STATUS_ACTIVE);
                }
            },
            'collections' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Collection::STATUS_ACTIVE);
                }
            },
            'category',
        ])
        ->where([
            'team_id' => $teamId,
            'id' => $toolId,
            ])
        ->first();

        $tool->name = html_entity_decode($tool->name);
        $tool->description = html_entity_decode($tool->description);
        $tool->results_insights = html_entity_decode($tool->results_insights);
        $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
        return $tool;
    }

    public function getToolById(int $toolId, bool $onlyActive = false)
    {
        $tool = Tool::with([
            'user',
            'tag',
            'team',
            'license',
            'programmingLanguages',
            'programmingPackages',
            'typeCategory',
            'publications' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Publication::STATUS_ACTIVE);
                }
            },
            'durs' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Dur::STATUS_ACTIVE);
                }
            },
            'collections' => function ($query) use ($onlyActive) {
                if ($onlyActive) {
                    $query->where('status', Collection::STATUS_ACTIVE);
                }
            },
            'category',
        ])
        ->where(['id' => $toolId])
        ->first();

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
    public function checkPublications(int $toolId, array $inPublications, int $userId = null)
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

    public function addPublicationHasTool(int $toolId, array $publication, int $userId = null)
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
    public function checkCollections(int $toolId, array $inCollections, int $userId = null)
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

    public function addCollectionHasTool(int $toolId, array $collection, int $userId = null)
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
