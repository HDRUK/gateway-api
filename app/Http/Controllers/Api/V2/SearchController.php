<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Search;
use App\Services\SearchAggregator;
use Laravel\Pennant\Feature;

class SearchController extends Controller
{
    public function __construct(protected SearchAggregator $aggregator)
    {
    }

    public function search(Search $request): JsonResponse
    {
        if (!Feature::active('V2_SearchAggregation')) {
            return response()->json([
                'message' => 'Resource not found',
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        }

        $type = $request->input('type');

        if (!$type) {
            return response()->json(['message' => "'type' is required"], 400);
        }

        $results = $this->aggregator->search(
            query: $request->input('query') ?? '',
            type: $type,
            params: $request->except(['query', 'type']),
        );

        $status = $results['message'] === 'success'
            ? Config::get('statuscodes.STATUS_OK.code')
            : 404;

        return response()->json($results, $status);
    }
}
