<?php

namespace App\Http\Controllers\Api\V2;

use Config;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SearchAggregator;
use Laravel\Pennant\Feature;

class SearchController extends Controller
{
    public function __construct(protected SearchAggregator $aggregator)
    {
        // Nothing, just need the DI.
    }

    public function search(Request $request)
    {
        if (!Feature::active('V2/Search/Aggregation')) {
            return response()->json([
                'message' => 'Resource not found',
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        }

        $input = $request->all();
        $results = $this->aggregator->search((isset($input['query']) ?? ''));

        return response()->json($results);
    }
}
