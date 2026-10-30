<?php

namespace App\Services\OnboardingForm;

use App\Context\OutputSchemaContext;
use App\Services\Gwdm\SourceSchema;
use Config;
use Illuminate\Support\Facades\Cache;
use MetadataManagementController as MMC;
use RuntimeException;

/**
 * The input schemas the onboarding form offers: the latest version of each
 * allowlisted model (form_hydration.onboarding_schemas) that TRASER lists.
 */
class OnboardingSchemaCatalogueService
{
    private const TTL = 300;

    public function __construct(
        private readonly OutputSchemaContext $outputSchemaContext,
    ) {
    }

    /**
     * @return array<int, array{model: string, version: string}>
     */
    public function schemas(): array
    {
        $allowlist = Config::get('form_hydration.onboarding_schemas', []);
        $key = 'onboarding.schemas.' . implode(',', $allowlist);

        $schemas = Cache::get($key);
        if ($schemas !== null) {
            return $schemas;
        }

        $available = MMC::listSchemas();
        $schemas = [];

        foreach ($allowlist as $model) {
            $versions = $available[$model] ?? [];
            usort($versions, fn (string $a, string $b) => version_compare($b, $a));

            if (count($versions)) {
                $schemas[] = ['model' => $model, 'version' => $versions[0]];
            }
        }

        if (count($schemas)) {
            Cache::put($key, $schemas, self::TTL);
        }

        return $schemas;
    }

    public function offers(string $model, ?string $version): bool
    {
        return in_array(['model' => $model, 'version' => $version], $this->schemas(), true);
    }

    /**
     * The schema to preselect: the dataset's source model, else the partner's
     * output model, else the first one offered. Matched by model, so a source
     * in an older version of an offered model selects the version offered.
     *
     * @return array{model: string, version: string}
     */
    public function defaultFor(?SourceSchema $source = null): array
    {
        $schemas = $this->schemas();

        if (!count($schemas)) {
            throw new RuntimeException('No onboarding schemas are available');
        }

        foreach ([$source ? $source->model : null, $this->outputSchemaContext->schemaModel()] as $model) {
            foreach ($schemas as $schema) {
                if ($model !== null && $schema['model'] === $model) {
                    return $schema;
                }
            }
        }

        return $schemas[0];
    }
}
