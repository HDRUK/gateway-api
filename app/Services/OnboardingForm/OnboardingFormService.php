<?php

namespace App\Services\OnboardingForm;

use App\Exceptions\MMCException;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Services\DatasetService;
use App\Services\Gwdm\SourceSchema;
use Config;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use MetadataManagementController as MMC;

class OnboardingFormService
{
    use ResolvesSchemaNodes;

    private const SCHEMA_TTL = 300;

    public function __construct(
        private readonly OnboardingSchemaCatalogueService $catalogue,
        private readonly OnboardingDefaultsService $defaultsService,
        private readonly DatasetService $datasetService,
    ) {
    }

    /**
     * A dataset's metadata is only loaded in its default schema. Switching to
     * any other schema opens a form holding just the summary, rather than a
     * translation.
     *
     * @throws InvalidArgumentException when the requested schema is not offered
     */
    public function hydrate(Team $team, ?Dataset $dataset, ?string $model, ?string $version): array
    {
        $latest = $dataset ? $dataset->latestVersion() : null;
        $default = $this->catalogue->defaultFor($latest ? $this->sourceSchema($latest) : null);

        if ($model !== null && !$this->catalogue->offers($model, $version)) {
            throw new InvalidArgumentException('Schema ' . $model . ' ' . $version . ' is not offered for onboarding');
        }

        $model ??= $default['model'];
        $version ??= $default['version'];

        $schema = $this->schemaFor($model, $version);

        $data = [
            'schemas' => $this->catalogue->schemas(),
            'model' => $model,
            'version' => $version,
            'schema' => $schema,
            'defaultValues' => $this->defaultsService->forTeam($team, $model, $version, $schema),
        ];

        if ($dataset) {
            $isDefault = $model === $default['model'] && $version === $default['version'];
            $data = array_merge($data, $isDefault
                ? $this->editableMetadata($dataset, $latest, $model, $version)
                : $this->summaryOnly($dataset, $latest, $default, $data['schema']));
        }

        return $data;
    }

    public function schemaFor(string $model, string $version): array
    {
        $key = 'onboarding.schema.' . $model . '.' . $version;

        $schema = Cache::get($key);
        if ($schema !== null) {
            return $schema;
        }

        $schema = MMC::getSchema($model, $version)['schema'] ?? [];

        if (count($schema)) {
            Cache::put($key, $schema, self::SCHEMA_TTL);
        }

        return $schema;
    }

    /**
     * The latest version's metadata in the given schema: the original
     * submission when it was made in exactly that schema, otherwise the stored
     * GWDM translated without validation. When that translation fails the form
     * opens empty; the web form has already warned that switching clears it.
     *
     * @return array{metadata: ?array}
     */
    public function editableMetadata(Dataset $dataset, ?DatasetVersion $latest, string $model, string $version): array
    {
        if (!$latest) {
            return [
                'metadata' => null,
            ];
        }

        $source = $this->sourceSchema($latest);

        $envelope = $this->datasetService->getReconstructedMetadataEnvelope(
            $dataset->id,
            $latest->version,
            validate: false,
            prefetched: $latest,
        );

        $original = $envelope['original_metadata'];
        if (isset($original['schemaModel'], $original['metadata'])) {
            $original = $original['metadata'];
        }

        if ($source->model === $model && $source->version === $version && !empty($original)) {
            return [
                'metadata' => $original,
            ];
        }

        try {
            $translated = MMC::translateDataModelType(
                json_encode(['metadata' => $envelope['metadata']]),
                $model,
                $version,
                Config::get('metadata.GWDM.name'),
                $envelope['gwdmVersion'],
                false,
                false,
            );
        } catch (MMCException $e) {
            $translated = ['wasTranslated' => false];
        }

        return [
            'metadata' => $translated['wasTranslated'] ? $translated['metadata'] : null,
        ];
    }

    private function sourceSchema(DatasetVersion $version): SourceSchema
    {
        $source = SourceSchema::fromColumns($version->only(['source_schema_model', 'source_schema_version']));

        return $source->model !== null ? $source : SourceSchema::declared(null, null, (array) $version->metadata);
    }

    /**
     * The summary loaded in the default schema, limited to the fields the
     * requested schema has. Every offered schema shares the summary's shape;
     * some add fields (CRUK's lead researcher), which are dropped.
     *
     * @return array{metadata: ?array}
     */
    private function summaryOnly(Dataset $dataset, ?DatasetVersion $latest, array $default, array $schema): array
    {
        $loaded = $this->editableMetadata($dataset, $latest, $default['model'], $default['version']);
        $summary = $loaded['metadata']['summary'] ?? null;
        $fields = $this->resolveNode($schema, $schema['properties']['summary'] ?? [])['properties'] ?? [];

        return [
            'metadata' => is_array($summary) && count($fields)
                ? ['summary' => array_intersect_key($summary, $fields)]
                : null,
        ];
    }
}
