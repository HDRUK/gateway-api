<?php

namespace App\Http\Traits;

use Config;
use Auditor;
use Exception;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Collection;
use App\Models\DatasetVersion;
use App\Models\Tool;
use App\Models\Dur;
use App\Models\User;
use App\Models\Publication;
use Illuminate\Support\Str;
use App\Models\CollectionHasDur;
use App\Models\CollectionHasTool;
use App\Models\CollectionHasKeyword;
use App\Models\CollectionHasPublication;
use App\Models\CollectionHasDatasetVersion;
use App\Models\CollectionHasUser;

trait CollectionsV2Helpers
{
    use IndexElastic;
    private function getCollectionActiveById(int $collectionId, ?bool $trimmed = false)
    {
        $collection = Collection::with([
            'keywords',
            'tools' => function ($query) use ($trimmed) {
                $query->where('tools.status', Tool::STATUS_ACTIVE)
                    ->when($trimmed, function ($q) {
                        $q->select(
                            "tools.id",
                            "tools.name",
                            "tools.created_at",
                            "tools.user_id"
                        );
                    });
            },
            'dur' => function ($query) use ($trimmed) {
                $query->where('dur.status', Dur::STATUS_ACTIVE)
                    ->when($trimmed, function ($q) {
                        $q->select([
                            'dur.id',
                            'dur.project_title',
                            'dur.organisation_name'
                        ]);

                    });
            },
            'publications' => function ($query) use ($trimmed) {
                $query->where('publications.status', Publication::STATUS_ACTIVE)
                    ->when($trimmed, function ($q) {
                        $q->select([
                            "publications.id",
                            "publications.paper_title",
                            "publications.authors",
                            "publications.url",
                            "publications.year_of_publication"
                        ]);
                    });
            },
            'users' => function ($query) use ($trimmed) {
                $query->when($trimmed, function ($q) {
                    $q->select([
                        'users.id',
                        'users.name',
                        'users.email',
                        ]);
                });
            },
            'datasetVersions' => function ($query) use ($trimmed) {
                $query->when($trimmed, function ($q) {
                    $q->selectRaw('
                    dataset_versions.id,dataset_versions.dataset_id,
                    short_title as shortTitle,
                    CONVERT(JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.populationSize")), SIGNED) as populationSize,
                    JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(dataset_versions.metadata), "$.metadata.summary.datasetType")) as datasetType
                ');
                });
            },
            'team',
        ])
        ->where(['id' => $collectionId])
        ->first();

        if ($collection) {
            if ($collection->image_link && !preg_match('/^https?:\/\//', $collection->image_link)) {
                $collection->image_link = Config::get('services.media.base_url') .  $collection->image_link;
            }

            if ($collection->users) {
                $collection->users->map(function (User $user) {
                    $currentEmail = $user->email;
                    [$username, $domain] = explode('@', $currentEmail);
                    $user->email = Str::mask($username, '*', 1, strlen($username) - 2) . '@' . Str::mask($domain, '*', 1, strlen($domain) - 2);
                    if (!is_null($user->secondary_email)) {
                        $currentSecondaryEmail = $user->secondary_email;
                        [$username, $domain] = explode('@', $currentSecondaryEmail);
                        $user->secondary_email = Str::mask($username, '*', 1, strlen($username) - 2) . '@' . Str::mask($domain, '*', 1, strlen($domain) - 2);
                    }
                    return $user;
                });
            }

            if ($collection->datasetVersions) {
                $collection->datasetVersions->map(function (DatasetVersion $dv) {
                    $dataset = Dataset::where('id', $dv->dataset_id)->first();
                    if ($dataset->status === Dataset::STATUS_ACTIVE) {
                        return $dv;
                    } else {
                        return null;
                    }
                });
            }
        }

        // teams.introduction, comes out with the chars decoded.. collection.description, does not...
        // I debugged it to high hell and got Big L involved and we assume there be dragons...
        // so this is a lil hotfix..
        $collection->description = htmlspecialchars_decode($collection->description);

        return $collection;
    }

    // datasets
    private function checkDatasets(int $collectionId, array $inDatasets, ?int $userId = null)
    {
        $collectionHasDatasetVersions = CollectionHasDatasetVersion::withTrashed()
                                            ->where('collection_id', $collectionId)
                                            ->select('dataset_version_id')
                                            ->get()
                                            ->toArray();

        $collectionHasDatasetVersionIds = [];
        if (count($collectionHasDatasetVersions)) {
            $collectionHasDatasetVersionIds = array_unique(convertArrayToArrayWithKeyName($collectionHasDatasetVersions, 'dataset_version_id'));
        }
        // Batch process this to avoid too many elastic reindexes
        $arrCreateCollectionHasDatasetVersion = [];
        $arrDeleteCollectionHasDatasetVersion = [];
        $teamIds = [];

        $datasetCreateIds = [];

        foreach ($inDatasets as $dataset) {
            $datasetInfo = Dataset::where('id', (int) $dataset['id'])->first();
            $datasetVersionLatest = $datasetInfo->latestVersion();
            $datasetVersionLatestId = $datasetVersionLatest->id;
            $teamIds[$dataset['id']] = $datasetInfo->team_id;
            $datasetVersions = DatasetVersion::where('dataset_id', (int) $dataset['id'])->select('id')->get()->toArray();

            $datasetVersionIds = convertArrayToArrayWithKeyName($datasetVersions, 'id');
            // This is the intersect of all versions of _this_ dataset, and all the versions in CollectionHasDatasetVersion for this Collection.
            $commonDatasetVersionIds = array_intersect($collectionHasDatasetVersionIds, $datasetVersionIds);

            // If the supplied datasets are not in the existing set, then add them
            if (count($commonDatasetVersionIds) === 0) {
                $arrCreateCollectionHasDatasetVersion[] = $this->buildAddCollectionHasDatasetVersion($collectionId, $dataset, $datasetVersionLatestId, $userId);
                $datasetCreateIds[] = $dataset['id'];
                continue;
            }
            // else, if the latest version isn't in there, then add it, and remove all the previously existing dataset versions that were linked
            // - this simply has the effect of updating the link to an already-linked dataset to its latest version id
            if (!in_array($datasetVersionLatestId, $commonDatasetVersionIds)) {
                $arrCreateCollectionHasDatasetVersion[] = $this->buildAddCollectionHasDatasetVersion($collectionId, $dataset, $datasetVersionLatestId, $userId);
                foreach ($commonDatasetVersionIds as $commonDatasetVersionId) {
                    $arrDeleteCollectionHasDatasetVersion[] = [
                        'collection_id' => $collectionId,
                        'dataset_version_id' => $commonDatasetVersionId];
                }
                continue;
            }

            // else if the latest version is already linked, then delete any links to older versions, and for the latest version, if it's been deleted previously, then re-add it
            if (in_array($datasetVersionLatestId, $commonDatasetVersionIds)) {
                foreach ($commonDatasetVersionIds as $commonDatasetVersionId) {
                    if ((int) $datasetVersionLatestId === (int) $commonDatasetVersionId) {
                        $checkCollectionWithLatestDatasetVersionActive = CollectionHasDatasetVersion::where([
                            'collection_id' => $collectionId,
                            'dataset_version_id' => $commonDatasetVersionId,
                        ])->first();

                        if (!is_null($checkCollectionWithLatestDatasetVersionActive)) {
                            continue;
                        }

                        $checkCollectionWithLatestDatasetVersionDeleted = CollectionHasDatasetVersion::onlyTrashed()
                            ->where([
                                'collection_id' => $collectionId,
                                'dataset_version_id' => $commonDatasetVersionId,
                            ])->first();

                        if (!is_null($checkCollectionWithLatestDatasetVersionDeleted)) {
                            CollectionHasDatasetVersion::withTrashed()->where([
                                'collection_id' => $collectionId,
                                'dataset_version_id' => $commonDatasetVersionId,
                            ])
                            ->limit(1)
                            ->update(['deleted_at' => null]);
                            continue;
                        }
                    } else {
                        $arrDeleteCollectionHasDatasetVersion[] = [
                             'collection_id' => $collectionId,
                             'dataset_version_id' => $commonDatasetVersionId,
                        ];
                    }
                }
            }
        }

        // Perform DB updates
        CollectionHasDatasetVersion::insert($arrCreateCollectionHasDatasetVersion);
        CollectionHasDatasetVersion::where($arrDeleteCollectionHasDatasetVersion)->forceDelete();

        // Reindex elastic
        foreach ($datasetCreateIds as $dataset) {
            $this->reindexElastic($dataset);
            if ($teamIds[$dataset]) {
                $this->reindexElasticDataProviderWithRelations((int) $teamIds[$dataset], 'dataset');
            }
        }
        // Now delete existing links to any dataset version that wasn't supplied.
        $collectionHasDatasetVersionsActive = CollectionHasDatasetVersion::where('collection_id', $collectionId)
                                            ->select('dataset_version_id')
                                            ->get()
                                            ->toArray();


        $wantedDatasetIds = convertArrayToArrayWithKeyName($inDatasets, 'id');
        $wantedDatasetVersionIds = DatasetVersion::whereIn('dataset_id', $wantedDatasetIds)->select('id')->get()->toArray();

        $unwantedDatasetVersionsIds = array_diff(
            array_column($collectionHasDatasetVersionsActive, 'dataset_version_id'),
            array_column($wantedDatasetVersionIds, 'id')
        );



        foreach ($unwantedDatasetVersionsIds as $datasetVersionId) {
            CollectionHasDatasetVersion::where([
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ])->forceDelete();
        }
    }

    // Build an array
    private function buildAddCollectionHasDatasetVersion(int $collectionId, array $dataset, int $datasetVersionId, ?int $userId = null)
    {
        try {
            $searchArray = [
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ];

            $arrCreate = [
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
                'deleted_at' => null,
            ];

            if (array_key_exists('user_id', $dataset)) {
                $arrCreate['user_id'] = (int) $dataset['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $dataset)) {
                $arrCreate['reason'] = $dataset['reason'];
            }

            if (array_key_exists('updated_at', $dataset)) { // special for migration
                $arrCreate['created_at'] = $dataset['updated_at'];
                $arrCreate['updated_at'] = $dataset['updated_at'];
            }
            $checkRow = CollectionHasDatasetVersion::where($searchArray)->first();
            if (is_null($checkRow)) {
                return $arrCreate;
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$arrCreate['user_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('buildAddCollectionHasDatasetVersion :: ' . $e->getMessage());
        }
    }


    private function checkInCollectionHasDatasetVersions(int $collectionId, int $datasetVersionId)
    {
        try {
            return CollectionHasDatasetVersion::withTrashed()->where([
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollectionHasDatasetVersions :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasDatasetVersions(int $collectionId, int $datasetVersionId)
    {
        try {
            return CollectionHasDatasetVersion::where([
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ])->forceDelete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'  .__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasDatasetVersions :: ' . $e->getMessage());
        }
    }

    // tools
    private function checkTools(int $collectionId, array $inTools, ?int $userId = null)
    {
        $cols = CollectionHasTool::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            if (!in_array($col->tool_id, $this->extractInputIdToArray($inTools))) {
                $this->deleteCollectionHasTools($collectionId, $col->tool_id);
            }
        }

        foreach ($inTools as $tool) {
            $checking = $this->checkInCollectionHasTools($collectionId, (int) $tool['id']);

            if (!$checking) {
                $this->addCollectionHasTool($collectionId, $tool, $userId);
            }
        }
    }

    private function addCollectionHasTool(int $collectionId, array $tool, ?int $userId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'tool_id' => $tool['id'],
            ];

            if (array_key_exists('user_id', $tool)) {
                $arrCreate['user_id'] = (int) $tool['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $tool)) {
                $arrCreate['reason'] = $tool['reason'];
            }

            if (array_key_exists('updated_at', $tool)) { // special for migration
                $arrCreate['created_at'] = $tool['updated_at'];
                $arrCreate['updated_at'] = $tool['updated_at'];
            }

            return CollectionHasTool::updateOrCreate(
                $arrCreate,
                [
                    'collection_id' => $collectionId,
                    'tool_id' => $tool['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$arrCreate['user_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasTool :: ' . $e->getMessage());
        }
    }

    private function checkInCollectionHasTools(int $collectionId, int $toolId)
    {
        try {
            return CollectionHasTool::where([
                'collection_id' => $collectionId,
                'tool_id' => $toolId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollectionHasTools :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasTools(int $collectionId, int $toolId)
    {
        try {
            return CollectionHasTool::where([
                'collection_id' => $collectionId,
                'tool_id' => $toolId,
            ])->forceDelete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasTools :: ' . $e->getMessage());
        }
    }

    // durs
    private function checkDurs(int $collectionId, array $inDurs, ?int $userId = null)
    {
        $cols = CollectionHasDur::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            if (!in_array($col->dur_id, $this->extractInputIdToArray($inDurs))) {
                CollectionHasDur::where([
                    'collection_id' => $collectionId,
                    'dur_id' => $col->dur_id,
                ])->forceDelete();
            }
        }

        foreach ($inDurs as $dur) {
            $checking = CollectionHasDur::where([
                'collection_id' => $collectionId,
                'dur_id' => (int) $dur['id'],
            ])->first();

            if (!$checking) {
                $this->addCollectionHasDur($collectionId, $dur, $userId);
            }
        }
    }

    private function addCollectionHasDur(int $collectionId, array $dur, ?int $userId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'dur_id' => $dur['id'],
            ];

            if (array_key_exists('user_id', $dur)) {
                $arrCreate['user_id'] = (int)$dur['user_id'];
            } elseif ($userId) {
                $arrCreate['user_id'] = $userId;
            }

            if (array_key_exists('reason', $dur)) {
                $arrCreate['reason'] = $dur['reason'];
            }

            if (array_key_exists('updated_at', $dur)) { // special for migration
                $arrCreate['created_at'] = $dur['updated_at'];
                $arrCreate['updated_at'] = $dur['updated_at'];
            }

            return CollectionHasDur::updateOrCreate(
                $arrCreate,
                [
                    'collection_id' => $collectionId,
                    'dur_id' => $dur['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$arrCreate['user_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasDur :: ' . $e->getMessage());
        }
    }

    // publications
    private function checkPublications(int $collectionId, array $inPublications, ?int $userId = null)
    {
        $cols = CollectionHasPublication::where(['collection_id' => $collectionId])->get();
        foreach ($cols as $col) {
            if (!in_array($col->publication_id, $this->extractInputIdToArray($inPublications))) {
                $this->deleteCollectionHasPublications($collectionId, $col->publication_id);
            }
        }

        foreach ($inPublications as $publication) {
            $checking = $this->checkInCollectionHasPublications($collectionId, (int) $publication['id']);

            if (!$checking) {
                $this->addCollectionHasPublication($collectionId, $publication, $userId);
            }
        }
    }

    private function addCollectionHasPublication(int $collectionId, array $publication, ?int $userId = null)
    {
        try {
            $arrCreate = [
                'collection_id' => $collectionId,
                'publication_id' => $publication['id'],
            ];

            if (array_key_exists('user_id', $publication)) {
                $arrCreate['user_id'] = (int) $publication['user_id'];
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

            return CollectionHasPublication::updateOrCreate(
                $arrCreate,
                [
                    'collection_id' => $collectionId,
                    'publication_id' => $publication['id'],
                ]
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('addCollectionHasPublication :: ' . $e->getMessage());
        }
    }

    private function checkInCollectionHasPublications(int $collectionId, int $publicationId)
    {
        try {
            return CollectionHasPublication::where([
                'collection_id' => $collectionId,
                'publication_id' => $publicationId,
            ])->first();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('checkInCollectionHasPublications :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasPublications(int $collectionId, int $publicationId)
    {
        try {
            return CollectionHasPublication::where([
                'collection_id' => $collectionId,
                'publication_id' => $publicationId,
            ])->forceDelete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasPublications :: ' . $e->getMessage());
        }
    }

    // keywords
    private function checkKeywords(int $collectionId, array $inKeywords)
    {
        $kws = CollectionHasKeyword::where('collection_id', $collectionId)->get();

        foreach ($kws as $kw) {
            $kwId = $kw->keyword_id;
            $checkKeyword = Keyword::where('id', $kwId)->first();

            if (!$checkKeyword) {
                $this->deleteCollectionHasKeywords($kwId);
                continue;
            }

            if (in_array($checkKeyword->name, $inKeywords)) {
                continue;
            }

            if (!in_array($checkKeyword->name, $inKeywords)) {
                $this->deleteCollectionHasKeywords($kwId);
            }
        }

        foreach ($inKeywords as $keyword) {
            $keywordId = $this->updateOrCreateKeyword($keyword)->id;
            $this->updateOrCreateCollectionHasKeywords($collectionId, $keywordId);
        }
    }

    private function updateOrCreateCollectionHasKeywords(int $collectionId, int $keywordId)
    {
        try {
            return CollectionHasKeyword::updateOrCreate([
                'collection_id' => $collectionId,
                'keyword_id' => $keywordId,
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('updateOrCreateCollectionHasKeywords :: ' . $e->getMessage());
        }
    }

    private function updateOrCreateKeyword($keyword)
    {
        try {
            return Keyword::updateOrCreate([
                'name' => $keyword,
            ], [
                'name' => $keyword,
                'enabled' => 1,
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('updateOrCreateKeyword :: ' . $e->getMessage());
        }
    }

    private function deleteCollectionHasKeywords($keywordId)
    {
        try {
            return CollectionHasKeyword::where(['keyword_id' => $keywordId])->forceDelete();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('deleteCollectionHasKeywords :: ' . $e->getMessage());
        }
    }

    private function extractInputIdToArray(array $input): array
    {
        $response = [];
        foreach ($input as $value) {
            $response[] = $value['id'];
        }

        return $response;
    }

    // add users to collections
    public function createCollectionUsers(int $collectionId, int $creatorId, array $collaboratorIds)
    {
        CollectionHasUser::create([
            'collection_id' => $collectionId,
            'user_id' => $creatorId,
            'role' => 'CREATOR',
        ]);

        $collaboratorIds = array_filter($collaboratorIds, function ($cId) use ($creatorId) {
            return (int)$cId !== (int)$creatorId;
        });

        foreach ($collaboratorIds as $collaboratorId) {
            CollectionHasUser::create([
                'collection_id' => $collectionId,
                'user_id' => $collaboratorId,
                'role' => 'COLLABORATOR',
            ]);
        }
    }

    // update users to collections
    public function updateCollectionUsers(int $collectionId, array $collaboratorIds)
    {
        CollectionHasUser::where([
            'collection_id' => $collectionId,
            'role' => 'COLLABORATOR',
        ])->forceDelete();

        foreach ($collaboratorIds as $collaboratorId) {
            CollectionHasUser::create([
                'collection_id' => $collectionId,
                'user_id' => $collaboratorId,
                'role' => 'COLLABORATOR',
            ]);
        }
    }

    public function prependUrl($collection)
    {
        if ($collection->image_link && !preg_match('/^https?:\/\//', $collection->image_link)) {
            $collection->image_link = Config::get('services.media.base_url') .  $collection->image_link;
        }

        return $collection;
    }
}
