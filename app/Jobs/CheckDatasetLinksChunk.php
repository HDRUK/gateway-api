<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\DatasetLinkCheckResult;
use App\Services\DatasetService;
use Http;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Checks the links in one chunk of datasets for dead (404) links. Dispatched
 * as a batch of these, one per chunk, from NightlyDatasetLinkCheckJob instead
 * of looping over every chunk inside a single job — keeps each job's runtime
 * bounded regardless of how many active datasets exist system-wide.
 */
class CheckDatasetLinksChunk implements ShouldQueue, Silenced
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 180;

    // Abort a response body before it's downloaded once it exceeds this size —
    // we only ever need the status code, never the body.
    private const MAX_RESPONSE_BYTES = 1_048_576;

    /** key => status code, captured from on_headers before we abort a body download */
    private array $headerStatuses = [];

    public function __construct(private readonly array $datasetIds)
    {
        $this->onQueue('indexing');
    }

    public function handle(DatasetService $datasetService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Reconstructing full GWDM metadata (replaying JSON patches back to the last
        // snapshot) for every dataset in the chunk can pull in a lot of JSON at once
        // for datasets with a deep version history — same rationale as ReindexTypesenseEntity.
        ini_set('memory_limit', config('jobs.link_check_memory_limit', '512M'));

        $datasets = Dataset::whereIn('id', $this->datasetIds)
            ->select(['id', 'team_id'])
            ->with('team:id,name')
            ->get();

        $targets = [];

        foreach ($datasets as $dataset) {
            $version = $dataset->latestVersion();

            if ($version === null) {
                continue;
            }

            $envelope = $datasetService->getReconstructedMetadataEnvelope(
                $dataset->id,
                $version->version,
                false,
                $version,
            );

            foreach ($this->extractUrls($envelope) as $url) {
                $targets[] = [
                    'dataset' => $dataset,
                    'dataset_name' => $version->title ?: ('Dataset #' . $dataset->id),
                    'url' => $url,
                ];
            }
        }

        if (empty($targets)) {
            return;
        }

        // We only ever report a genuine 404 — "the origin server confirms this
        // resource doesn't exist" is the one signal we can stand behind. Everything
        // else (403, 429, 5xx, timeouts, WAF/bot challenges, ...) is inherently
        // ambiguous: it can mean blocked, rate-limited, or misconfigured just as
        // easily as dead, and we can't tell those apart from here — so we don't
        // report it, full stop. No HEAD requests either: a lot of frameworks/CMSs
        // only wire up GET route handlers, and HEAD alone produces false 404s on
        // pages that are completely real (this codebase's sibling reachability job,
        // NightlyDatasetTestJob, avoids HEAD for the same reason).
        $keys = array_keys($targets);

        // Pass 1: cheap ranged GET.
        $keys = $this->keepOnlyNotFound($keys, $targets, fn (Pool $pool, $key) => $pool->as($key)
            ->timeout(20)
            ->withHeaders($this->requestHeaders() + ['Range' => 'bytes=0-0'])
            ->withOptions($this->boundedRequestOptions($key))
            ->get($targets[$key]['url']));

        // Pass 2: plain GET, no Range header — a differently-shaped request, so a
        // 404 that was really a Range-handling quirk won't reproduce here.
        $keys = $this->keepOnlyNotFound($keys, $targets, fn (Pool $pool, $key) => $pool->as($key)
            ->timeout(20)
            ->withHeaders($this->requestHeaders())
            ->withOptions($this->boundedRequestOptions($key))
            ->get($targets[$key]['url']));

        // Pass 3: one last check, serial and uncontended, no pool concurrency at
        // all — rules out a 404 that only reproduced because of a burst of parallel
        // requests to that host (rate limiting, local resolver contention, etc.).
        foreach ($keys as $key) {
            usleep(500_000);

            $this->headerStatuses = [];
            $responses = $this->pooledRequests([$key], fn (Pool $pool, $k) => $pool->as($k)
                ->timeout(20)
                ->withHeaders($this->requestHeaders())
                ->withOptions($this->boundedRequestOptions($k))
                ->get($targets[$k]['url']));

            if ($this->resolveStatus($responses[$key], $key, $targets[$key]['url']) === 404) {
                $target = $targets[$key];
                $this->recordFailure($target['dataset'], $target['dataset_name'], $target['url']);
            }
        }
    }

    /**
     * Runs the given request against every key and returns only the ones that came
     * back exactly 404 — everything else (success, or any other ambiguous failure
     * mode) is dropped from further consideration here.
     */
    private function keepOnlyNotFound(array $keys, array $targets, callable $requestFactory): array
    {
        if (empty($keys)) {
            return [];
        }

        $this->headerStatuses = [];
        $responses = $this->pooledRequests($keys, $requestFactory);

        return array_values(array_filter($keys, fn ($key) => $this->resolveStatus(
            $responses[$key],
            $key,
            $targets[$key]['url']
        ) === 404));
    }

    /**
     * Guzzle's default "GuzzleHttp/7" user agent (and lack of an Accept header) gets
     * blocked outright by a lot of bot/WAF protection (Cloudflare, Akamai, etc.),
     * producing 403s on links that are actually fine. We identify ourselves plainly
     * rather than spoofing a browser, which is enough to get past most of them.
     */
    private function requestHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (compatible; HDRUKGatewayLinkChecker/1.0; +' . config('gateway.gateway_url') . ')',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];
    }

    /**
     * Guzzle request options that abort a response as soon as headers arrive if the
     * body would be large, capturing the status code first so an intentional abort
     * still counts as a successful check rather than a false "dead link".
     */
    private function boundedRequestOptions(int $key): array
    {
        return [
            'on_headers' => function (ResponseInterface $response) use ($key) {
                $this->headerStatuses[$key] = $response->getStatusCode();

                $length = (int) $response->getHeaderLine('Content-Length');

                if ($length > self::MAX_RESPONSE_BYTES) {
                    throw new RuntimeException('link check: response too large, skipping body download');
                }
            },
        ];
    }

    /**
     * Runs the pool in fixed-size batches rather than firing every link in a
     * dataset chunk at once — an unbounded pool can queue up dozens of requests
     * against the same host, and requests stuck waiting for a free connection can
     * end up timing out even though the resource itself is fine.
     */
    private function pooledRequests(array $keys, callable $requestFactory): array
    {
        $batchSize = (int) config('gateway.nightly_dataset_link_check_http_batch_size', 10);
        $responses = [];

        foreach (array_chunk($keys, max(1, $batchSize)) as $batchKeys) {
            $responses += Http::pool(fn (Pool $pool) => collect($batchKeys)->mapWithKeys(
                fn ($key) => [$key => $requestFactory($pool, $key)]
            ));
        }

        return $responses;
    }

    private function resolveStatus(mixed $response, int $key, string $url): ?int
    {
        if ($response instanceof Response) {
            return $response->status();
        }

        if (isset($this->headerStatuses[$key])) {
            return $this->headerStatuses[$key];
        }

        // Genuine connection-level failure (timeout, DNS, refused, TLS) rather than
        // an HTTP error — log the actual reason so a wave of "dead" links that work
        // fine in a browser is diagnosable instead of just showing up as null.
        Log::warning('CheckDatasetLinksChunk: link check failed at the connection level', [
            'url' => $url,
            'reason' => $response instanceof Throwable ? $response->getMessage() : get_debug_type($response),
        ]);

        return null;
    }

    private function recordFailure(Dataset $dataset, string $datasetName, string $url): void
    {
        DatasetLinkCheckResult::create([
            'dataset_id' => $dataset->id,
            'team_id' => $dataset->team?->id,
            'team_name' => $dataset->team?->name,
            'url' => $url,
            'status_code' => 404,
        ]);
    }

    /**
     * Recursively pulls every http(s) URL out of every string value in the reconstructed
     * GWDM metadata document, regardless of which field it's nested under.
     */
    private function extractUrls(array $metadata): array
    {
        $urls = [];
        $this->walkForUrls($metadata, $urls);

        $urls = array_filter(array_unique($urls), fn ($url) => !$this->isImageUrl($url));

        return array_values($urls);
    }

    /**
     * We only care about pages being reachable, not assets like team/dataset
     * logos — e.g. https://media.prod.hdruk.cloud/teams/some-team.png.
     */
    private function isImageUrl(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return (bool) preg_match('/\.(png|jpe?g|gif|svg|webp|bmp|ico|tiff?)$/i', $path);
    }

    private function walkForUrls(mixed $value, array &$urls): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->walkForUrls($item, $urls);
            }
            return;
        }

        if (!is_string($value)) {
            return;
        }

        // Some metadata fields pack multiple links into one string joined by a
        // ";,;" delimiter with no surrounding whitespace, which would otherwise
        // get swallowed into a single match along with the next URL.
        $value = str_replace(';,;', ' ', $value);

        if (preg_match_all('/https?:\/\/[^\s"\'<>]+/i', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $urls[] = rtrim($match, '.,;)');
            }
        }
    }

    public function tags(): array
    {
        return ['nightly_dataset_link_check'];
    }
}
