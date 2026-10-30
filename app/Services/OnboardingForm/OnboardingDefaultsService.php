<?php

namespace App\Services\OnboardingForm;

use App\Context\GwdmVersionContext;
use App\Exceptions\MMCException;
use App\Models\Dataset;
use App\Models\Team;
use App\Services\Gwdm\GwdmHandlerFactory;
use Config;
use Illuminate\Support\Str;
use MetadataManagementController as MMC;

class OnboardingDefaultsService
{
    use ResolvesSchemaNodes;

    public function __construct(
        private readonly GwdmVersionContext $gwdmVersionContext,
        private readonly GwdmHandlerFactory $gwdmHandlerFactory,
    ) {
    }

    /**
     * Prefill values for a new dataset in the given schema: the team as
     * custodian plus the team's most common values, built as partial GWDM and
     * translated. Empty when TRASER cannot translate the partial document.
     *
     * The translation maps fill required fields with placeholders (a 1970
     * start date, "Other" frequency) so their output validates. Any value an
     * empty document translates to is one of those, so it is dropped; empty
     * lists and nulls are kept, as a required list (observations) needs one.
     * An empty object decodes to [] as well, so the schema tells them apart.
     */
    public function forTeam(Team $team, string $model, string $version, array $schema): array
    {
        $gwdmVersion = $this->gwdmVersionContext->targetVersion();

        $gwdm = [
            'summary' => [
                'contactPoint' => $team->contact_point,
                'publisher' => [
                    'name' => $team->name,
                    'gatewayId' => (string) $team->id,
                ],
            ],
            'accessibility' => [
                'access' => [
                    'dataController' => $team->name,
                    'dataProcessor' => $team->name,
                ],
            ],
        ];

        $datasets = Dataset::where('team_id', $team->id)->get()
            ->map(fn (Dataset $dataset) => ['metadata' => $dataset->latestVersion()->metadata])
            ->all();

        if (count($datasets)) {
            foreach ($this->gwdmHandlerFactory->resolve($gwdmVersion)->defaultValuePaths() as $path) {
                $value = $this->mostCommonValue($path, $datasets);
                if ($value !== null) {
                    data_set($gwdm, Str::after($path, 'metadata.'), $value);
                }
            }
        }

        $translated = $this->translate($gwdm, $model, $version, $gwdmVersion);
        $placeholders = $this->translate(
            ['summary' => new \stdClass()],
            $model,
            $version,
            $gwdmVersion,
        );

        $defaults = $this->withoutPlaceholders($translated, $placeholders, $schema, $schema['properties'] ?? []);

        foreach ($this->formDefaults($team) as $path => $value) {
            if ($value !== null && $this->hasPath($schema, $path)) {
                data_set($defaults, $path, $value);
            }
        }

        return $defaults;
    }

    /**
     * Values the original onboarding form set directly on its fields, which
     * GWDM has nowhere to carry through translation.
     */
    private function formDefaults(Team $team): array
    {
        $logo = $team->team_logo;
        if ($logo !== null && strlen(trim($logo)) && !preg_match('/^https?:\/\//', $logo)) {
            $logo = Config::get('services.media.base_url') . $logo;
        }

        return [
            'summary.dataCustodian.logo' => $logo !== null && strlen(trim($logo)) ? $logo : null,
            'summary.dataCustodian.description' => $team->name,
            'summary.dataCustodian.memberOf' => $team->member_of,
            'accessibility.access.jurisdiction' => ['UK'],
            'accessibility.formatAndStandards.language' => ['en'],
            'coverage.materialType' => ['None/not available'],
        ];
    }

    private function hasPath(array $schema, string $path): bool
    {
        $node = ['properties' => $schema['properties'] ?? []];
        foreach (explode('.', $path) as $key) {
            if (!isset($node['properties'][$key])) {
                return false;
            }
            $node = $this->resolveNode($schema, $node['properties'][$key]);
        }

        return true;
    }

    private function translate(array $gwdm, string $model, string $version, string $gwdmVersion): array
    {
        try {
            $translated = MMC::translateDataModelType(
                json_encode(['metadata' => $gwdm]),
                $model,
                $version,
                Config::get('metadata.GWDM.name'),
                $gwdmVersion,
                false,
                false,
            );
        } catch (MMCException $e) {
            return [];
        }

        return $translated['wasTranslated'] ? (array) $translated['metadata'] : [];
    }

    private function withoutPlaceholders(array $values, array $placeholders, array $schema, array $properties): array
    {
        $kept = [];
        foreach ($values as $key => $value) {
            $placeholder = $placeholders[$key] ?? null;
            $node = $this->resolveNode($schema, $properties[$key] ?? []);
            if (is_array($value) && !array_is_list($value)) {
                $nested = $this->withoutPlaceholders(
                    $value,
                    is_array($placeholder) ? $placeholder : [],
                    $schema,
                    $node['properties'] ?? [],
                );
                if (count($nested)) {
                    $kept[$key] = $nested;
                }
            } elseif ($value === [] && isset($node['properties'])) {
                continue;
            } elseif (
                !array_key_exists($key, $placeholders)
                || $value !== $placeholder
                || $value === null
                || $value === []
            ) {
                $kept[$key] = $value;
            }
        }

        return $kept;
    }

    private function mostCommonValue(string $path, array $datasets): mixed
    {
        $values = array();
        foreach ($datasets as $dataset) {
            // $dataset['metadata'] holds the raw dataset_versions.metadata envelope;
            // handler-owned paths are relative to that envelope (see defaultValuePaths()).
            $v = data_get($dataset, 'metadata.' . $path);
            $values[] = is_scalar($v) ? (string)$v : '';
        }

        $countMap = array_count_values($values);
        arsort($countMap);
        $mostCommon = array_keys($countMap)[0];

        return $mostCommon === '' ? null : $mostCommon;
    }
}
