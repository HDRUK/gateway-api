<?php

namespace App\MetadataManagementController;

use Config;
use Mauro;
use Exception;

use App\Models\Dataset;

use App\Exceptions\MMCException;

use Illuminate\Support\Facades\Http;

class MetadataManagementController {
    /**
     * Translates an incoming dataset payload via TRASER 
     * from $inputSchema and $inputVersion to $outputSchema and
     * $outputVersion
     * 
     * @param string $dataset The incoming dataset to validate
     * 
     * @return array
     */
    public function translateDataModelType(
        string $dataset,
        string $outputSchema,
        string $outputVersion,
        string $inputSchema,
        string $inputVersion): array
    {
        try {
            $urlString = sprintf("%s/translate?output_schema=%s&output_version=%s&input_schema=%s&input_version=%s",
                env('TRASER_SERVICE_URL'),
                $outputSchema,
                $outputVersion,
                $inputSchema,
                $inputVersion
            );

            // !! Dragons ahead !!
            // Suggest that no one change this, ever. Took hours
            // to debug. Laravel's usual ::post facade explicitly
            // sets application/json content-type, but for some
            // reason the data was being tampered with and the
            // traser service couldn't validate the body. This
            // ::withBody is an old school call that does *exactly*
            // the same thing, but for some reason, this works
            // whereas ::post does not?!
            // 
            // TODO: Needs further investigation. Enigma alert.
            $response = Http::withBody(
                $dataset, 'application/json'
            )->post($urlString);

            if ($response->status() === 200) {
                return $response->json();
            }

            return [];
        } catch (Exception $e) {
            throw new MMCException($e->getMessage());
        }
    }

    /**
     * Attempts to validate that the passed $dataset is in
     * GWDM format
     * 
     * @param string $dataset The incoming dataset to validate
     * @param string $input_schema The schema to validate against
     * @param string $input_version The schema version to validate against
     * 
     * @return bool
     */
    public function validateDataModelType(string $dataset, string $input_schema, string $input_version): bool
    {
        try {
            $urlString = sprintf("%s/validate?input_schema=%s&input_version=%s",
                env('TRASER_SERVICE_URL'),
                $input_schema,
                $input_version
            );

            // !! Dragons ahead !!
            // Suggest that no one change this, ever. Took hours
            // to debug. Laravel's usual ::post facade explicitly
            // sets application/json content-type, but for some
            // reason the data was being tampered with and the
            // traser service couldn't validate the body. This
            // ::withBody is an old school call that does *exactly*
            // the same thing, but for some reason, this works
            // whereas ::post does not?!.
            // 
            // TODO: Needs further investigation. Enigma alert.
            $response = Http::withBody(
                $dataset, 'application/json'
            )->post($urlString);

            return ($response->status() === 200);
        } catch (Exception $e) {
            throw new MMCException($e->getMessage());
        }
    }

    /**
     * Creates an instance of a dataset record within the database
     * 
     * @param array $input The array object that makes up the metadata
     *  to store
     * 
     * @return Dataset
     */
    public function createDataset(array $input): Dataset
    {
        $dataset = Dataset::create($input);
        return $dataset;
    }

    public function createMauroDataModel(array $user, array $team, array $input): array
    {
        if ($this->validateTeamExistsInMauro($team)) {
            return Mauro::createDataModel(
                $input['label'],
                $input['short_description'],
                $user['name'],
                $team['name'],
                $team['mdm_folder_id'],
                $input
            );
        }

        return [];
    }

    public function validateTeamExistsInMauro(array $team): bool
    {
        if (!empty($team['mdm_folder_id'])) {
            return true;
        }

        return false;
    }


    /**
     * Calls a re-indexing of Elastic search when data changes in
     * such a fashion that demands it
     * 
     * @return void
     */
    public function reindexElastic(array $dataset, string $datasetId): void
    {
        // Get named entities
        try {
            $datasetMatch = Dataset::where('datasetid', $datasetId)
                ->with(['namedEntities'])
                ->first()
                ->toArray();

            $namedEntities = array();
            foreach ($datasetMatch['named_entities'] as $n) {
                $namedEntities[] = $n['name'];
            }

            $toIndex = json_encode([
                'abstract' => $dataset['summary']['abstract'],
                'keywords' => $dataset['summary']['keywords'],
                'description' => $dataset['summary']['description'],
                'shortTitle' => $dataset['summary']['shortTitle'],
                'title' => $dataset['summary']['title'],
                'publisher_name' => $dataset['summary']['publisher']['publisherName'],
                'named_entities' => $namedEntities
            ]);

            $elasticUrl = sprintf("%s/datasets/_doc/%s",
                env("ELASTIC_SERVICE_URL"),
                $datasetMatch['id']
            );

            $response = Http::withoutVerifying()
                ->withBody($toIndex, 'application/json')
                ->withBasicAuth(env('ELASTIC_USER'), env('ELASTIC_PASSWORD'))
                ->put($elasticUrl);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}