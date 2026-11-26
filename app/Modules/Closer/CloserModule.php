<?php

namespace App\Modules\Closer;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Modules\Closer\Jobs\HarvestCloserWave;
use App\Modules\Contracts\EnrichmentModule;
use App\Modules\EnrichmentContext;
use App\Modules\EnrichmentResult;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Enriches a dataset with a summary of its CLOSER Discovery item, which for a
 * PRUK cohort is usually a StudyUnit, i.e. one wave/life stage of a study,
 * and queues a harvest of the wave's datasets, variables and statistics.
 *
 * The CLOSER identifier is taken from, in order:
 *   1. params['identifier'] (agency/id, DDI URN or Discovery URL),
 *   2. services.closer.dataset_identifiers[dataset id] (manual override),
 *   3. the first DDI URN (urn:ddi:agency:id[:version]) in the dataset's latest metadata.
 *
 * Only named fields are copied out of CLOSER's response, so personal data
 * such as VersionResponsibility (a staff email) is never stored.
 */
class CloserModule implements EnrichmentModule
{
    private const URN_PATTERN = '/urn:ddi:[A-Za-z0-9.\-]+:[A-Za-z0-9\-]+(?::\d+)?/';

    public function __construct(private readonly CloserClient $closer)
    {
    }

    /** Reads the database at most once (latest metadata); never calls CLOSER. */
    public function supports(EnrichmentContext $context): bool
    {
        return $this->identifierFor($context) !== null;
    }

    public function sourceVersion(EnrichmentContext $context): ?string
    {
        ['agency' => $agency, 'id' => $id] = $this->requireIdentifier($context);

        $response = $this->closer->get("item/{$agency}/{$id}/history");
        if ($response->status() === 404) {
            return null;
        }
        if (! $response->successful()) {
            throw new RuntimeException("CLOSER history request failed: HTTP {$response->status()}.");
        }

        $versions = array_filter(array_column($response->json() ?? [], 'Version'), 'is_numeric');

        return $versions ? (string) max($versions) : null;
    }

    public function enrich(EnrichmentContext $context): EnrichmentResult
    {
        ['agency' => $agency, 'id' => $id] = $this->requireIdentifier($context);

        $response = $this->closer->get("item/{$agency}/{$id}");
        if ($response->status() === 404) {
            return EnrichmentResult::empty();
        }
        if (! $response->successful()) {
            throw new RuntimeException("CLOSER item request failed: HTTP {$response->status()}.");
        }

        $version = $response->json('Version');
        $version = is_numeric($version) ? (string) $version : null;

        $payload = [
            'identifier' => "urn:ddi:{$agency}:{$id}".($version !== null ? ":{$version}" : ''),
            'url' => rtrim((string) config('services.closer.discovery_url'), '/')."/item/{$agency}/{$id}",
            ...$this->summarise((string) $response->json('Item')),
            'version_date' => $response->json('VersionDate'),
        ];

        // The summary is cheap; the full wave (variables, statistics) is harvested
        // separately. Only reached when the version changed or a pull was forced.
        HarvestCloserWave::dispatch($agency, $id);

        return EnrichmentResult::ok(
            array_filter($payload, fn ($value) => $value !== null && $value !== '' && $value !== []),
            $version,
        );
    }

    /**
     * @return array{agency: string, id: string, version: ?int}|null
     */
    public function identifierFor(EnrichmentContext $context): ?array
    {
        $candidates = [
            $context->params['identifier'] ?? null,
            config('services.closer.dataset_identifiers', [])[$context->subjectId] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return CloserClient::parseIdentifier($candidate);
            }
        }

        if (is_a($context->subjectType, Dataset::class, true)
            && preg_match(self::URN_PATTERN, $this->latestMetadataText($context->subjectId), $match)) {
            return CloserClient::parseIdentifier($match[0]);
        }

        return null;
    }

    /** @return array{agency: string, id: string, version: ?int} */
    private function requireIdentifier(EnrichmentContext $context): array
    {
        return $this->identifierFor($context)
            ?? throw new InvalidArgumentException('No CLOSER identifier for this subject.');
    }

    /** The raw JSON of the dataset's latest metadata; only searched as text. */
    private function latestMetadataText(int|string $datasetId): string
    {
        return (string) DatasetVersion::query()
            ->where('dataset_id', $datasetId)
            ->orderByDesc('version')
            ->toBase()
            ->value('metadata');
    }

    /** Pull the readable fields out of a DDI 3.3 fragment. */
    private function summarise(string $xml): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $xml !== '' && $document->loadXML($xml, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (! $loaded) {
            throw new RuntimeException('CLOSER returned an item that is not valid XML.');
        }

        $xpath = new DOMXPath($document);
        $el = fn (string $name) => "*[local-name()='{$name}']";
        $first = fn (string $query) => ($node = $xpath->query($query)->item(0)) ? trim($node->textContent) : null;
        $all = fn (string $query) => array_map(fn ($n) => trim($n->textContent), iterator_to_array($xpath->query($query)));
        $count = fn (string $name) => $xpath->query("//{$el($name)}")->length;

        $abstract = $first("//{$el('Abstract')}/{$el('Content')}");

        return [
            'type' => $document->documentElement?->firstElementChild?->localName,
            'title' => $first("//{$el('Citation')}/{$el('Title')}/{$el('String')}"),
            'abstract' => $abstract !== null ? trim(strip_tags($abstract)) : null,
            ...$this->customFields($xpath, $el),
            'start_date' => $first("//{$el('TemporalCoverage')}//{$el('StartDate')}"),
            'end_date' => $first("//{$el('TemporalCoverage')}//{$el('EndDate')}"),
            'kind_of_data' => $all("//{$el('KindOfData')}"),
            'analysis_unit' => $first("//{$el('AnalysisUnit')}"),
            'publisher' => $first("//{$el('PublisherName')}/{$el('String')}"),
            'counts' => [
                'datasets' => $count('PhysicalInstanceReference'),
                'data_collections' => $count('DataCollectionReference'),
            ],
        ];
    }

    /** Colectica custom fields (e.g. LifeStage), keyed in snake_case. */
    private function customFields(DOMXPath $xpath, callable $el): array
    {
        $fields = [];

        foreach ($xpath->query("//{$el('UserAttributePair')}") as $pair) {
            $key = trim($xpath->query($el('AttributeKey'), $pair)->item(0)?->textContent ?? '');
            $value = json_decode($xpath->query($el('AttributeValue'), $pair)->item(0)?->textContent ?? '', true);

            if ($key === 'extension:CustomField' && is_array($value) && isset($value['DisplayLabel'])
                && is_string($value['StringValue'] ?? null)) {
                $fields[Str::snake($value['DisplayLabel'])] = $value['StringValue'];
            }
        }

        return $fields;
    }
}
