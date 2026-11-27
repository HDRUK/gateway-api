<?php

namespace App\Services\Search;

use App\Context\PartnerContext;
use App\Models\DatasetVersion;
use App\Services\TypesenseService;

/**
 * Finds datasets by the summary fields they hold. Each term is matched against
 * some summary field, by meaning (embedding distance)
 * and by its words; datasets are then kept when they match all terms, or
 * any of them.
 *
 * Given dataset ids (the comparison view), it returns exactly those
 * datasets in that order, with an empty list for each term they don't match.
 */
class MetadataSummarySearch
{
    public const MATCH_ALL = 'all';

    public const MATCH_ANY = 'any';

    public function __construct(
        private readonly TypesenseService $typesense,
        private readonly PartnerContext $partnerContext,
    ) {
    }

    /**
     * @param  string[]  $terms
     * @param  int[]|null  $datasetIds
     */
    public function search(array $terms, string $match = self::MATCH_ALL, ?array $datasetIds = null): array
    {
        $terms = array_values($terms);
        $baseFilter = $this->baseFilter($datasetIds);

        $response = $terms === [] ? [] : $this->typesense->multiSearch(array_map(
            fn (string $term) => $this->termSearch($term, $baseFilter),
            $terms,
        ));

        // [datasetId => [termIndex => document[]]], plus the dataset's title
        // and publisher from whichever hit carried them first.
        $matches = [];
        $details = [];
        foreach ($response['results'] ?? [] as $termIndex => $result) {
            foreach ($result['grouped_hits'] ?? [] as $group) {
                $datasetId = (int) ($group['group_key'][0] ?? 0);
                foreach ($group['hits'] ?? [] as $hit) {
                    $docMatch = $this->getDocumentMatch($hit, $terms[$termIndex] ?? '');
                    if ($docMatch === null) {
                        continue;
                    }
                    $matches[$datasetId][$termIndex][] = $docMatch;
                    $details[$datasetId] ??= [
                        'title' => $hit['document']['datasetTitle'] ?? '',
                        'publisherName' => $hit['document']['publisherName'] ?? '',
                    ];
                }
            }
        }

        // Hybrid ranking interleaves the two kinds of hit; show word matches
        // first, then the closest by meaning.
        foreach ($matches as &$byTerm) {
            foreach ($byTerm as &$docMatches) {
                usort($docMatches, fn ($a, $b) => [$a['matchedBy'] !== 'words', $a['distance'] ?? 1.0]
                    <=> [$b['matchedBy'] !== 'words', $b['distance'] ?? 1.0]);
            }
        }
        unset($byTerm, $docMatches);

        $ids = $datasetIds !== null
            ? array_values(array_unique(array_map('intval', $datasetIds)))
            : $this->rank($matches, count($terms), $match);

        $ids = array_slice($ids, 0, (int) config('typesense.summary_embedding_config.max_datasets', 50));
        $details += $this->datasetDetails(array_values(array_diff($ids, array_keys($details))));
        // Requested ids outside the partner scope (or no longer indexed) drop out.
        $ids = array_values(array_filter($ids, fn (int $id) => isset($details[$id])));

        return [
            'terms' => $terms,
            'match' => $match,
            'datasets' => array_map(fn (int $id) => [
                'id' => $id,
                'title' => $details[$id]['title'] ?? null,
                'publisherName' => $details[$id]['publisherName'] ?? null,
                'matches' => array_map(
                    fn (int $termIndex) => $matches[$id][$termIndex] ?? [],
                    array_keys($terms),
                ),
            ], $ids),
        ];
    }

    private function termSearch(string $term, string $filter): array
    {
        $threshold = (float) config('typesense.summary_embedding_config.distance_threshold', 0.70);

        return [
            'collection' => MetadataSummaryIndex::collectionName(),
            'q' => $term,
            'query_by' => 'text,embedding',
            'exclude_fields' => 'embedding',
            'filter_by' => $filter,
            'vector_query' => "embedding:([], k: 2000, distance_threshold: {$threshold})",
            // Not drop_tokens_threshold: 0 - with no keyword hits it empties
            // the vector hits too. Partial word matches are sifted in getDocumentMatch().
            'prefix' => false,
            'num_typos' => 1,
            // Gives keyword hits a vector distance too, for sifting and ordering.
            'rerank_hybrid_matches' => true,
            'group_by' => 'dataset_id',
            'group_limit' => (int) config('typesense.summary_embedding_config.per_dataset', 25),
            'per_page' => 250,
        ];
    }

    private function baseFilter(?array $datasetIds): string
    {
        $readable = PartnerContext::readableContextsFor($this->partnerContext->getPartner());
        $clauses = ['partnerContext:=['.implode(',', array_map(
            fn (string $c) => '`'.str_replace('`', '', $c).'`',
            $readable,
        )).']'];

        if ($datasetIds !== null) {
            $clauses[] = 'dataset_id:=['.implode(',', array_map('intval', $datasetIds ?: [0])).']';
        }

        return implode(' && ', $clauses);
    }

    /**
     * A hit as an array, or null when it only shares some of the term's
     * words and isn't close in meaning ("measured at clinic" for
     * "depression measures").
     */
    private function getDocumentMatch(array $hit, string $term): ?array
    {
        $doc = $hit['document'] ?? [];
        $distance = isset($hit['vector_distance']) ? (float) $hit['vector_distance'] : null;
        $wordCount = count(preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY));
        $allWords = (int) ($hit['text_match_info']['tokens_matched'] ?? 0) >= max(1, $wordCount);
        $close = $distance !== null
            && $distance <= (float) config('typesense.summary_embedding_config.distance_threshold', 0.70);

        if (! $allWords && ! $close) {
            return null;
        }

        return [
            'id' => $doc['id'] ?? null,
            'field' => $doc['field'] ?? '',
            'text' => $doc['text'] ?? '',
            'matchedBy' => $allWords ? 'words' : 'meaning',
            'distance' => $distance !== null ? round($distance, 3) : null,
        ];
    }

    /**
     * Dataset ids, best first: those matching more terms, then those whose
     * closest document are closest overall.
     */
    private function rank(array $matches, int $termCount, string $match): array
    {
        $scored = [];
        foreach ($matches as $datasetId => $byTerm) {
            if ($match === self::MATCH_ALL && count($byTerm) < $termCount) {
                continue;
            }

            $distance = 0.0;
            foreach ($byTerm as $docMatches) {
                $distance += min(array_map(fn ($v) => $v['distance'] ?? 1.0, $docMatches));
            }
            $scored[] = [$datasetId, count($byTerm), $distance];
        }

        usort($scored, fn ($a, $b) => [$b[1], $a[2]] <=> [$a[1], $b[2]]);

        return array_column($scored, 0);
    }

    /**
     * Title and publisher for datasets without a matching document, from the
     * dataset index so the partner scope still applies.
     */
    private function datasetDetails(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $result = $this->typesense->rawSearch((new DatasetVersion())->searchableAs(), '*', [
            'filter_by' => $this->baseFilter($ids),
            'include_fields' => 'dataset_id,title,publisherName',
            'per_page' => count($ids),
        ]);

        $details = [];
        foreach ($result['hits'] ?? [] as $hit) {
            $doc = $hit['document'];
            $details[(int) $doc['dataset_id']] = [
                'title' => $doc['title'] ?? '',
                'publisherName' => $doc['publisherName'] ?? '',
            ];
        }

        return $details;
    }
}
