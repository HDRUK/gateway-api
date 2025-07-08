<?php

namespace App\Console\Commands;

use App\Models\Dur;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Collection;
use App\Models\Publication;

use Illuminate\Console\Command;

class PrecheckGAT7330 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:precheck-gat-7330';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run this command to output a summary of the existing entities and their relationships under the "old" archiving/deletion behaviour';

    private function classnameFromClass($class)
    {
        $exp = explode('\\', $class);
        return end($exp);
    }

    private function entityRow(string $entity)
    {
        return [
                $this->classnameFromClass($entity),
                $entity::withTrashed()->count(),
                $entity::onlyTrashed()->count(),
                $entity::withTrashed()->where('status', 'ACTIVE')->count(),
                $entity::onlyTrashed()->where('status', 'ACTIVE')->count(),
                $entity::withTrashed()->where('status', 'DRAFT')->count(),
                $entity::onlyTrashed()->where('status', 'DRAFT')->count(),
                $entity::withTrashed()->where('status', 'ARCHIVED')->count(),
                $entity::onlyTrashed()->where('status', 'ARCHIVED')->count(),
        ];
    }

    private function classToIdString(string $className)
    {
        // Split the string into an array based on uppercase letters
        $words = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY);
        
        // Join the array elements with an underscore
        $snakeCase = implode('_', $words);
        
        // Convert the resulting string to lowercase
        $snakeCase = strtolower($snakeCase);
        
        return $snakeCase . '_id';
    }

    private function idStringToClass(string $idString)
    {
        // Remove '_id' suffix
        $string = substr($idString,0,-3);
        
        // Split by underscores
        $words = explode('_', $string);
        
        // Convert the resulting strings to Capitalised case
        $capitalisedWords = array_map('ucfirst', $words);

        // Glue strings together
        return implode('', $capitalisedWords);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // We run a series of checks, from highest level to most granular, outputting data and stats at each point to build up a picture of the "before"
        // state of all entities and their relationships

        // For each entity type,
        // - get a count of how many are active/draft/archived/?deleted

        // - split by team and user, get a count of how many are active/draft/archived/?deleted

        // - output a list of each entity and its self-reported links to other entities


        // Overview table of entities by status:
        $headers = ['Entity', 'total', 'of which deleted', 'active', 'of which deleted', 'draft', 'of which deleted', 'archived', 'of which deleted'];
        $overviewEntities = [Collection::class, Dataset::class, Dur::class, Publication::class, Tool::class];

        echo("\n\n-----------------------------------------------------\n  Summary of entities by status and deleted_at value.\n-----------------------------------------------------\n");

        $data = array_map('self::entityRow', $overviewEntities);
        $this->table($headers, $data);

        echo("\n\n---------------------------------------------------\n  Summary of entities with inconsistencies.\n---------------------------------------------------\n");

        // Run checks - are all archived = soft-deleted?
        $headers = ['Entity', 'deleted not archived', 'archived not deleted'];
        $data = array_map(function ($entity) {
            return [
                $entity,
                json_encode(array_column($entity::onlyTrashed()->where('status', '!=', 'archived')->select('id')->get()->toArray(), 'id')),
                json_encode(array_column($entity::where('status', '=', 'archived')->select('id')->get()->toArray(), 'id')),
            ];
        }, $overviewEntities);
        $this->table($headers, $data);

        echo("\n\n---------------------------------------------------\n  Summary of relationships.\n---------------------------------------------------\n");

        // Overview table of Has relations
        $entityTypes = [Collection::class, DatasetVersion::class, Dur::class, Publication::class, Tool::class];

        $hasRelations = [
            'Collection' => [
                'CollectionHasDatasetVersion' => 'dataset_version_id',
                'CollectionHasDur' => 'dur_id',
                'CollectionHasPublication' => 'publication_id',
                'CollectionHasTool' => 'tool_id',
            ],
            'DatasetVersion' => [
                'CollectionHasDatasetVersion' => 'collection_id',
                'DatasetVersionHasTool' => 'tool_id',
                'DurHasDatasetVersion' => 'dur_id',
                'PublicationHasDatasetVersion' => 'publication_id',
            ],
            'Dur' => [
                'CollectionHasDur' => 'collection_id',
                'DurHasDatasetVersion' => 'dataset_version_id',
                'DurHasPublication' => 'publication_id',
                'DurHasTool' => 'tool_id',
            ],
            'Publication' => [
                'CollectionHasPublication' => 'collection_id',
                'DurHasPublication' => 'dur_id',
                'PublicationHasDatasetVersion' => 'dataset_version_id',
                'PublicationHasTool' => 'tool_id',
            ],
            'Tool' => [
                'CollectionHasTool' => 'collection_id',
                'DatasetVersionHasTool' => 'dataset_version_id',
                'DurHasTool' => 'dur_id',
                'PublicationHasTool' => 'publication_id',
            ],
        ];

        $hasArray = [];
        foreach ($entityTypes as $entity1) {
            $hasRow = [$entity1];
            foreach ($entityTypes as $entity2) {
                $hasClassName = $this->classnameFromClass($entity1) . 'Has' . $this->classnameFromClass($entity2);
                if (class_exists('App\\Models\\' . $hasClassName)) {
                    $test = new ('App\\Models\\' . $hasClassName)();
                    $hasRow[] = $test->count();
                } else {
                    $hasRow[] = '';
                }
            }
            $hasArray[] = $hasRow;
        }
        $hasHeaders = ['', ...$entityTypes];
        $this->table($hasHeaders, $hasArray);

        // For each entity type, print out all its entries, including status and its links to other entities. We want to ultimately
        // be able to say "this is what a user sees on the Gateway" in each scenario and have it match.

        echo("\n\n---------------------------------------------------\n  Complete record of all relationships by entity.\n---------------------------------------------------\n");

        $headerRow = ['entity type', 'entity id', 'relation type', 'relation count', 'relationId'];
        $csvHeaderRow = ['entity type', 'id', 'Collection', 'Dur', 'DatasetVersion', 'Publication', 'Tool'];

        $data = [];
        foreach ($entityTypes as $entityType) {
            if (in_array($entityType, [Collection::class, Publication::class])) {
                $entitiesOfThisType = $entityType::withTrashed()->orderBy('id')->select('id', 'team_id')->get();
            }
            elseif (in_array($entityType, [DatasetVersion::class])) {
                $entitiesOfThisType = $entityType::withTrashed()->orderBy('id')->select('id')->get();
            }
            else {
                $entitiesOfThisType = $entityType::withTrashed()->orderBy('id')->select('id', 'team_id', 'user_id')->get();
            }
            foreach ($entitiesOfThisType as $entity) {
                $csvDataRow = ['type' => null, 'id' => null, 'team_id' => null, 'user_id' => null, 'Collection' => null, 'Dur' => null, 'DatasetVersion' => null, 'Publication' => null, 'Tool' => null];
                $csvDataRow['type'] = $this->classnameFromClass($entityType);
                $csvDataRow['id'] = $entity->id;
                $csvDataRow['team_id'] = $entity->team_id ?? null;
                if (in_array($entityType, [DatasetVersion::class])) {
                    $thisDataset = Dataset::where('id', $entity->dataset_id)->first();
                    $csvDataRow['team_id'] = $thisDataset->team_id ?? null;
                }
                $csvDataRow['user_id'] = $entity->user_id ?? null;
                foreach ($hasRelations[$this->classnameFromClass($entityType)] as $relation => $idName) {
                    $relationEntries = 
                        (new ('App\\Models\\' . $relation)())
                        ::where($this->classToIdString($this->classnameFromClass($entityType)), $entity->id)
                        ->get()
                        ->toArray();
                    $relatedEntryIds = array_column($relationEntries, $idName);

                    $relatedEntityType = $this->idStringToClass($idName);

                    sort($relatedEntryIds);
                    $data[] = [$this->classnameFromClass($entityType), $entity->id, $relation, count($relationEntries), json_encode($relatedEntryIds)];
                    $csvDataRow[$relatedEntityType] = (is_null($relatedEntryIds) || empty($relatedEntryIds)) ? null : json_encode($relatedEntryIds);
                }
                $csvData[] = $csvDataRow;
            }
        }

        // $this->table($headerRow, $data);
        // $this->table($csvHeaderRow, $csvData);
        var_dump(json_encode($csvData));
    }
}
