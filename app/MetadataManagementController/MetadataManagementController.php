<?php

namespace App\MetadataManagementController;

use Exception;
use App\Exceptions\MMCException;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Library;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use App\Http\Traits\GetValueByPossibleKeys;

class MetadataManagementController
{
    use GetValueByPossibleKeys;

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
        string $inputSchema = null,
        string $inputVersion = null,
        bool $validateInput = true,
        bool $validateOutput = true,
        string $subsection = null,
    ): array {
        try {

            $queryParams = [
                'output_schema' => $outputSchema,
                'output_version' => $outputVersion,
                'input_schema' => $inputSchema,
                'input_version' => $inputVersion,
                'validate_input' => $validateInput ? '1' : 0 ,
                'validate_output' => $validateOutput ? '1' : 0 ,
                'subsection' => $subsection,
            ];

            $urlString = env('TRASER_SERVICE_URL', 'http://localhost:8002') . '/translate?' . http_build_query($queryParams);

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
                $dataset,
                'application/json'
            )->post($urlString);

            $wasTranslated =  $response->status() === 200;
            $metadata = null;
            $message = null;
            if ($wasTranslated) {
                $metadata = $response->json();
                $message = 'translation successful';
            } else {
                $message = $response->json();
            }

            return array(
                'traser_message' => $message,
                'wasTranslated' => $wasTranslated,
                'metadata' => $metadata,
                'statusCode' => $response->status(),
            );

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
    public function validateDataModelType(string &$dataset, string $input_schema, string $input_version): bool
    {
        try {
            $urlString = sprintf(
                '%s/validate?input_schema=%s&input_version=%s',
                env('TRASER_SERVICE_URL', 'http://localhost:8002'),
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
                $dataset,
                'application/json'
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
    public function createDataset(array &$input): Dataset
    {
        return Dataset::create($input);
    }

    public function createDatasetVersion(array &$input): DatasetVersion
    {
        return DatasetVersion::create($input);
    }

    /**
     * (Soft) Deletes a dataset from the system by $id
     *
     * @param string $id The dataset to delete
     *
     * @return void
     */
    public function deleteDataset(string $id, bool $setToArchived = false): void
    {
        try {
            $dataset = Dataset::with('versions')->where('id', (int)$id)->first();
            if (!$dataset) {
                throw new Exception('Dataset with id=' . $id . ' cannot be found');
            }
            $dataset->deleted_at = Carbon::now();

            // maintain compatibility with v1 (set status to archived) while supporting v2 (don't set status)
            if ($setToArchived) {
                $dataset->status = Dataset::STATUS_ARCHIVED;
            }
            $dataset->save();

            foreach ($dataset->versions as $metadata) {
                $metadata->deleted_at = Carbon::now();
                $metadata->save();
            }

            Library::where(['dataset_id' => $id])->delete();

            unset(
                $dataset
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getOnboardingFormHydrated(string $name, string $version, ?string $dataTypes): array
    {
        try {
            $queryParams = [
                'name' => $name,
                'version' => $version,
                'dataTypes' => $dataTypes
            ];

            $urlString = env('TRASER_SERVICE_URL') . '/get/form_hydration?' . http_build_query($queryParams);
            $response = Http::get($urlString);

            unset(
                $queryParams,
            );

            return $response->json();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
