<?php

namespace App\Http\Traits;

use Config;
use Exception;
use App\Models\Dur;
use App\Models\Dataset;
use Illuminate\Support\Arr;
use App\Models\DatasetVersion;
use App\Models\DataAccessTemplate;
use MetadataManagementController as MMC;
use App\Http\Requests\V2\Dataset\GetDataset;

trait DatasetsV2Helpers
{
    private function getDatasetDetails(Dataset $dataset, GetDataset $request)
    {
        // Get only the very latest metadata version ID, and process all related
        // objects on this, rather than excessively calling latestDataset()-><relation>.
        $latestVersionID = $dataset->latestVersionID($dataset->id);

        // Inject attributes via the dataset version table
        // notes Calum 12th August 2024...
        // - This is a mess.. why is `publications_count` returning something different than dataset->allPublications??
        // - Tools linkage not returned
        // - For the FE I just need a tools linkage count so i'm gonna return `count(dataset->allActiveTools)` for now
        // - Same for collections
        // - Leaving this as it is as im not 100% sure what any FE knock-on effect would be
        //
        // LS - Have replaced publications and dur counts with a raw count of linked relations via
        // the *_has_* lookups.
        $dataset->setAttribute('durs_count', $this->countActiveDursForDatasetVersion($latestVersionID));
        $dataset->setAttribute('publications_count', $this->countActivePublicationsForDatasetVersion($latestVersionID));
        // This needs looking into, as helpful as attributes are, they're actually
        // really poor in terms of performance. It'd be quicker to directly mutate
        // a model in memory. That is, however, lazy, and better still would be
        // to translate these to raw sql, as I have done above.
        $dataset->setAttribute('tools_count', count($dataset->allActiveTools));
        $dataset->setAttribute('collections_count', count($dataset->allActiveCollections));
        $dataset->setAttribute('spatialCoverage', $dataset->allSpatialCoverages  ?? []);
        $dataset->setAttribute('durs', $dataset->allActiveDurs  ?? []);
        $dataset->setAttribute('publications', $dataset->allActivePublications  ?? []);
        $dataset->setAttribute('named_entities', $dataset->allNamedEntities  ?? []);
        $dataset->setAttribute('collections', $dataset->allActiveCollections  ?? []);

        $outputSchemaModel = $request->query('schema_model');
        $outputSchemaModelVersion = $request->query('schema_version');

        // Return the latest metadata for this dataset
        if (!($outputSchemaModel && $outputSchemaModelVersion)) {
            $withLinks = DatasetVersion::where('id', $latestVersionID)
                ->with(['linkedDatasetVersions'])
                ->first();
            if ($withLinks) {
                $dataset->setAttribute('versions', [$withLinks]);
            }
        }

        if ($outputSchemaModel && $outputSchemaModelVersion) {
            $latestVersion = $dataset->latestVersion();

            $translated = MMC::translateDataModelType(
                json_encode($latestVersion->metadata),
                $outputSchemaModel,
                $outputSchemaModelVersion,
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version'),
            );

            if ($translated['wasTranslated']) {
                $withLinks = DatasetVersion::where('id', $latestVersion['id'])
                    ->with(['reducedLinkedDatasetVersions'])
                    ->first();
                $withLinks['metadata'] = json_encode(['metadata' => $translated['metadata']]);
                $dataset->setAttribute('versions', [$withLinks]);
            } else {
                return [null, response()->json([
                    'message' => 'failed to translate',
                    'details' => $translated
                ], 400)];
            }
        } elseif ($outputSchemaModel) {
            throw new Exception('You have given a schema_model but not a schema_version');
        } elseif ($outputSchemaModelVersion) {
            throw new Exception('You have given a schema_version but not schema_model');
        }

        $teamPublishedDARTemplates = DataAccessTemplate::where([['team_id', $dataset['team']['id']], ['published', 1]])->pluck('id');
        $dataset['team']['has_published_dar_template'] = !$teamPublishedDARTemplates->isEmpty();

        return [$dataset, null];
    }

    /**
     * Extracts metadata from the given mixed input.
     *
     * @param Mixed $metadata
     * @return array
     */
    private function extractMetadata(Mixed $metadata)
    {
        if (is_array($metadata) && Arr::has($metadata, 'metadata.metadata')) {
            $metadata = $metadata['metadata'];
        } elseif (is_array($metadata) && !Arr::has($metadata, 'metadata')) {
            $metadata = [
                'metadata' => $metadata,
            ];
        }

        if (is_string($metadata) && isJsonString($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        // Pre-process check for incoming data from a resource that passes strings
        // when we expect an associative array. FMA passes strings, this
        // is a safe-guard to ensure execution is unaffected by other data types.


        if (isset($metadata['metadata']) && is_string($metadata['metadata']) && isJsonString($metadata['metadata'])) {
            $tmpMetadata['metadata'] = json_decode($metadata['metadata'], true);
            unset($metadata['metadata']);
            $metadata = $tmpMetadata;
        }

        // Metadata should not contain HTML encodings
        $this->decodeMetadataHTML($metadata['metadata']);
        return $metadata;
    }

    private function decodeMetadataHTML(Array &$metadataArr) {
        foreach ($metadataArr as $key => &$value) {
            if (is_array($value)) {
                $this->decodeMetadataHTML($value); 
            } else {
                $metadataArr[$key] = html_entity_decode($value);
            }
        }
    }
}
