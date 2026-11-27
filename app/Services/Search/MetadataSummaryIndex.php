<?php

namespace App\Services\Search;

use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Log;
use App\Services\Search\SearchTraits\IndexesEmbeddedDocuments;

/**
 * The Typesense collection behind summary metadata search: one document per field in the summary section.metadata, embedded from its readable name and
 * description.
 *
 * Only the dataset's indexed version (DatasetVersion::shouldBeSearchable)
 * contributes, so the documents always match what dataset search shows.
 */
class MetadataSummaryIndex
{
    use IndexesEmbeddedDocuments;

    public function __construct(private readonly TypesenseService $typesense)
    {
    }

    public static function configKey(): string
    {
        return 'typesense.summary_embedding_config';
    }
    protected static function defaultCollectionName(): string
    {
        return 'metadata_summary';
    }

    public static function documentNoun(): string
    {
        return 'documents';
    }
    // Keeps the existing 'skipped' reason rather than the default 'no documents'.
    protected static function emptyReason(): string
    {
        return 'no summary metadata';
    }

    /**
     * Document indexing follows Scout's driver, so tests and environments
     * without Typesense skip it rather than fail.
     */
    public static function enabled(): bool
    {
        return config('scout.driver') === 'typesense'
            && (bool) config('typesense.summary_embedding_config.enabled', true);
    }

    public static function collectionName(): string
    {
        return config('scout.prefix', '').config('typesense.summary_embedding_config.collection', 'metadata_summary');
    }

    /**
     * The partners whose documents are indexed (typesense.summary_embedding_config.partners);
     * empty means every partner.
     *
     * @return string[]
     */
    public static function partners(): array
    {
        return array_values(array_filter(array_map(
            fn (string $partner) => strtoupper(trim($partner)),
            explode(',', (string) config('typesense.summary_embedding_config.partners', ''))
        )));
    }

    public static function indexesPartner(string $partner): bool
    {
        $partners = self::partners();

        return $partners === [] || in_array(strtoupper($partner), $partners, true);
    }

    public function schema(): array
    {
        return [
            'name' => self::collectionName(),
            'fields' => [
                ['name' => 'dataset_id', 'type' => 'string', 'facet' => true],
                ['name' => 'datasetTitle', 'type' => 'string', 'optional' => true],
                ['name' => 'publisherName', 'type' => 'string', 'optional' => true, 'facet' => true],
                ['name' => 'publisherGatewayId', 'type' => 'string', 'optional' => true],
                ['name' => 'partnerContext', 'type' => 'string', 'optional' => true],
                ['name' => 'datasetType', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                ['name' => 'datasetSubType', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                ['name' => 'populationSize', 'type' => 'int32', 'optional' => true],
                ['name' => 'inPipeline', 'type' => 'string', 'optional' => true, 'facet' => true],

                ['name' => 'field',              'type' => 'string',   'facet' => true],
                ['name' => 'text',               'type' => 'string'],
                
                [
                    'name' => 'embedding',
                    'type' => 'float[]',
                    'embed' => [
                        'from' => ['text'],
                        'model_config' => ['model_name' => config('typesense.summary_embedding_config.model')],
                    ],
                ],
            ],
        ];
    }
 

    /**
     * The summary documents for a dataset version, read from the
     * reconstructed GWDM envelope (delta versions carry no metadata).
     */
    public function documentsFor(DatasetVersion $version): array
    {
        $envelope = app(DatasetService::class)->getReconstructedMetadataEnvelope(
            $version->dataset_id,
            $version->version,
            false,
            $version,
        );

        $fields = app(GwdmHandlerFactory::class)->resolve($envelope['gwdmVersion'] ?? $version->gwdm_version ?? '2.0')
        ->toSearchableFields($envelope);

        $base = [            
            'dataset_id' => (string) $version->dataset_id,
            'datasetTitle' => (string) ($version->title ?: data_get($envelope, 'metadata.summary.title', '')),
            'publisherName' => (string) ($fields['publisherName'] ?? ''),
            'publisherGatewayId' => (string) data_get($envelope, 'metadata.summary.publisher.gatewayId', ''),
            'partnerContext' => (string) ($version->dataset?->partner_context ?? 'HDRUK'), 
            'datasetType' => array_values((array) ($fields['dataType'] ?? [])),
            'datasetSubType' => array_values((array) ($fields['dataSubType'] ?? [])),
            'populationSize' => (int) ($fields['populationSize'] ?? -1),
            'inPipeline' => (string) data_get($envelope, 'metadata.summary.inPipeline', ''),
        ];

        $documents = [];
        foreach ($this->fieldTexts($version, $envelope, $fields) as $field => $text) {
            $documents[] = $base + [
                'id'    => "{$version->dataset_id}-{$field}",
                'field' => $field,
                'text'  => $text,
            ];
        }

        return $documents;
    }

    private function fieldTexts(DatasetVersion $version, array $envelope, array $fields): array {
        $title = $this->clean((string) ($version->title ?: data_get($envelope, 'metadata.summary.title', '')));
        $aliases = data_get($envelope, 'metadata.summary.datasetAliases') ?? [];

        return array_filter([
                'title' => $title,
                'keywords' => $this->listText((array) ($fields['keywords'] ?? [])),
                'aliases' => $this->listText((array) $aliases),
                'abstract' => $this->clean((string) ($fields['abstract'] ?? '')),
                'description' => $this->clean((string) ($fields['description'] ?? '')),
            ], fn (string $text) => $text !== '');
    }

    /**
     * Avoid repeats that are different only in capitals.
     *
     * @param  array<mixed>  $items
     */
    private function listText(array $items): string {
        $unique = [];
        foreach ($items as $item) {
            $item = $this->clean((string) $item);
            $key = mb_strtolower($item);
            if ($item !== '' && ! isset($unique[$key])) {
                $unique[$key] = $item;
            }
        }
        return implode(', ', $unique);
    }

    private function clean(string $text): string {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
        // remove email addresses and markdown symbols
        $text = preg_replace('/[\w.+-]+@[\w-]+(\.[\w-]+)+/u', '[email removed]', $text) ?? '';
        $text = preg_replace('/[*_#`>|]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

}
