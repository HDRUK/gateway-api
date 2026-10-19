<?php

namespace Database\Factories;

use App\Models\Dataset;
use App\Services\Gwdm\SourceSchema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Traits\MockExternalApis;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatasetVersion>
 */
class DatasetVersionFactory extends Factory
{
    use MockExternalApis;

    /** What the onboarding form declares (web SCHEMA_NAME / SCHEMA_VERSION). */
    private const FORM_SCHEMA_MODEL = 'HDRUK';
    private const FORM_SCHEMA_VERSION = '4.1.0';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dataset = Dataset::all()->random();
        return [
            'dataset_id' => $dataset->id,
            'metadata' => json_encode($this->getMetadata()),
            'version' => fake()->numberBetween(1, 100),
            'provider_team_id' => $dataset->team_id,
            'application_type' => fake()->word(),
            // Resolved from the dataset actually assigned - seeders override
            // dataset_id after the random pick above.
            'source_schema_model' => fn (array $attributes) => $this->sourceSchemaFor($attributes)->model,
            'source_schema_version' => fn (array $attributes) => $this->sourceSchemaFor($attributes)->version,
        ];
    }

    /**
     * The source schema real ingestion would record for this dataset's origin:
     * the form declares its schema, GMI is HDRUK with an unreported version,
     * and API callers may or may not declare one.
     */
    private function sourceSchemaFor(array $attributes): SourceSchema
    {
        $origin = Dataset::whereKey($attributes['dataset_id'])->value('create_origin');

        return match ($origin) {
            Dataset::ORIGIN_GMI => SourceSchema::gmi(),
            Dataset::ORIGIN_MANUAL => SourceSchema::declared(self::FORM_SCHEMA_MODEL, self::FORM_SCHEMA_VERSION),
            default => $this->apiSchema($attributes['dataset_id']),
        };
    }

    // Stable per dataset, so every version of one dataset agrees.
    private function apiSchema(int $datasetId): SourceSchema
    {
        return $datasetId % 2 === 0
            ? SourceSchema::declared(self::FORM_SCHEMA_MODEL, self::FORM_SCHEMA_VERSION)
            : SourceSchema::unknown();
    }
}
