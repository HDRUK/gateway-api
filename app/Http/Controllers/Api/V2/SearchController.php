<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Search;
use App\Jobs\DeferredProviderSearch;
use App\Services\SearchAggregator;
use Laravel\Pennant\Feature;

class SearchController extends Controller
{
    private const FEATURE_FLAG = 'V2_SearchAggregation';
    private const TYPESENSE_FEATURE_FLAG = 'TypesenseSearch';

    public function __construct(protected SearchAggregator $aggregator)
    {
    }

    public function search(Search $request): JsonResponse
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $type = $request->input('type');

        if (!$type) {
            return response()->json(['message' => "'type' is required"], 400);
        }

        $query  = $request->input('query') ?? '';
        $only   = $request->input('providers') ?? [];
        $params = $request->except(['query', 'type', 'providers']);

        $immediateResults  = $this->aggregator->searchImmediate($query, $type, $params, $only);
        $deferredProviders = $this->aggregator->getDeferredProviders($type, $only);

        $pending = [];
        $token   = null;

        if (!empty($deferredProviders)) {
            $token        = 'srch_' . Str::random(32);
            $ttl          = config('gateway.search_deferred_ttl');
            $pendingNames = array_map(fn ($p) => $p->getShortName(), $deferredProviders);

            Cache::put("search:deferred:{$token}:pending", $pendingNames, $ttl);

            foreach ($deferredProviders as $provider) {
                DeferredProviderSearch::dispatch(
                    get_class($provider),
                    $token,
                    $query,
                    $type,
                    $params
                );
            }

            $pending = $pendingNames;
        }

        if (empty($immediateResults) && empty($pending)) {
            return response()->json(['message' => 'failed', 'data' => null], 404);
        }

        $data = [
            'query'   => $query,
            'type'    => $type,
            'results' => $immediateResults,
        ];

        if (!empty($pending)) {
            $data['pending']   = $pending;
            $data['token']     = $token;
            $data['token_ttl'] = $ttl;
        }

        return response()->json(['message' => 'success', 'data' => $data]);
    }

    public function searchResults(string $token): JsonResponse
    {
        if (!Feature::active(self::FEATURE_FLAG)) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $pendingNames = Cache::get("search:deferred:{$token}:pending");

        if ($pendingNames === null) {
            return response()->json(['message' => 'Token not found or expired'], 404);
        }

        $results      = [];
        $stillPending = [];

        foreach ($pendingNames as $shortName) {
            $result = Cache::get("search:deferred:{$token}:result:{$shortName}");

            if ($result !== null) {
                $results[$shortName] = $result;
            } else {
                $stillPending[] = $shortName;
            }
        }

        return response()->json([
            'message' => 'success',
            'data'    => [
                'results' => $results,
                'pending' => $stillPending,
            ],
        ]);
    }

    public function searchTypesense(Search $request): JsonResponse
    {
        if (!Feature::active(self::TYPESENSE_FEATURE_FLAG)) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        // TODO
    }
}
