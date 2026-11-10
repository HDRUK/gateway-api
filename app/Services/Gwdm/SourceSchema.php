<?php

namespace App\Services\Gwdm;

/**
 * The schema a dataset version was submitted in - its ingestion format, such
 * as HDRUK 4.1.0 or CRUK 1.0.0 - as distinct from the GWDM version it is
 * stored as. Persisted on dataset_versions.source_schema_model/_version.
 */
final readonly class SourceSchema
{
    /** GMI pull ingestion only accepts HDRUK metadata. */
    public const GMI_MODEL = 'HDRUK';

    public function __construct(
        public ?string $model,
        public ?string $version,
    ) {
    }

    /**
     * What the submitter declared. An explicit declaration (e.g. the
     * ?input_schema/?input_version query parameters) wins over the
     * schemaModel/schemaVersion the onboarding form puts in the metadata
     * envelope. A version without a model identifies nothing, so is dropped.
     */
    public static function declared(?string $model, ?string $version, array $envelope = []): self
    {
        $model = self::clean($model) ?? self::clean($envelope['schemaModel'] ?? null);
        $version = self::clean($version) ?? self::clean($envelope['schemaVersion'] ?? null);

        return $model === null ? self::unknown() : new self($model, $version);
    }

    /**
     * GMI rows are always HDRUK. TRASER detects which HDRUK version but does
     * not report it, so the version is left unknown.
     */
    public static function gmi(): self
    {
        return new self(self::GMI_MODEL, null);
    }

    public static function unknown(): self
    {
        return new self(null, null);
    }

    public static function fromColumns(array $row): self
    {
        return new self(
            $row['source_schema_model'] ?? null,
            $row['source_schema_version'] ?? null,
        );
    }

    /** @return array{source_schema_model: ?string, source_schema_version: ?string} */
    public function toColumns(): array
    {
        return [
            'source_schema_model' => $this->model,
            'source_schema_version' => $this->version,
        ];
    }

    /** @return array{model: string, version: ?string}|null null when unknown */
    public function toArray(): ?array
    {
        return $this->model === null
            ? null
            : ['model' => $this->model, 'version' => $this->version];
    }

    private static function clean(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
