<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use Illuminate\Console\Command;

class DecodeDatasetMetadataEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:decode-dataset-metadata-entities
        {--dataset-id=* : Scope to specific dataset id(s); comma-separated and/or repeated}
        {--dry-run : Log intended changes without writing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fully decode HTML-entity-encoded text left in dataset_versions.metadata/title/short_title by the removed SanitizeMiddleware compounding entity encoding on repeated saves.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $datasetIds = $this->resolveDatasetIds();
        $dryRun = (bool) $this->option('dry-run');

        $query = DatasetVersion::select('id', 'dataset_id', 'metadata', 'title', 'short_title');
        if (! empty($datasetIds)) {
            $query->whereIn('dataset_id', $datasetIds);
        }

        $changed = 0;
        $unchanged = 0;

        foreach ($query->get() as $version) {
            $metadataOriginal = $version->metadata;
            $metadataDecoded = is_array($metadataOriginal) ? $this->decodeAllStrings($metadataOriginal) : $metadataOriginal;
            $metadataChanged = is_array($metadataOriginal) && $metadataDecoded !== $metadataOriginal;

            $titleOriginal = $version->title;
            $titleDecoded = $this->decodeAllStrings($titleOriginal);
            $titleChanged = $titleDecoded !== $titleOriginal;

            $shortTitleOriginal = $version->short_title;
            $shortTitleDecoded = $this->decodeAllStrings($shortTitleOriginal);
            $shortTitleChanged = $shortTitleDecoded !== $shortTitleOriginal;

            if (! $metadataChanged && ! $titleChanged && ! $shortTitleChanged) {
                $unchanged++;
                continue;
            }

            $changed++;
            $this->info(
                ($dryRun ? '[dry-run] ' : '')
                    ."dataset_version id={$version->id} dataset_id={$version->dataset_id}: decoded entity-encoded text"
            );

            if (! $dryRun) {
                $update = [];
                if ($metadataChanged) {
                    $update['metadata'] = $metadataDecoded;
                }
                if ($titleChanged) {
                    $update['title'] = $titleDecoded;
                }
                if ($shortTitleChanged) {
                    $update['short_title'] = $shortTitleDecoded;
                }
                DatasetVersion::where('id', $version->id)->update($update);
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."done: {$changed} changed, {$unchanged} unchanged.");

        return self::SUCCESS;
    }

    /**
     * Recursively decode every string leaf in $value, looping html_entity_decode()
     * until it stops changing so any pre-existing depth of over-encoding is fully
     * unwound, regardless of how many times a record was resaved.
     */
    private function decodeAllStrings(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->decodeAllStrings($item), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $decoded = $value;
        while (($next = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8')) !== $decoded) {
            $decoded = $next;
        }

        return $decoded;
    }

    /**
     * @return array<int, int>
     */
    private function resolveDatasetIds(): array
    {
        $raw = $this->option('dataset-id');
        if (empty($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            foreach (explode(',', $value) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $ids[] = (int) $piece;
                }
            }
        }

        return array_unique($ids);
    }
}
