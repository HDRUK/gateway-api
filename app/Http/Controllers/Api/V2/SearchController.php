<?php

namespace App\Http\Controllers\Api\V2;

use App\Services\SearchAggregator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function __construct(protected SearchAggregator $aggregator)
    {
        // Nothing, just need the DI.
    }

    public function search(Request $request)
    {
        $input = $request->all();
        $results = $this->aggregator->search((isset($input['query']) ?? ''));

        return response()->json($results);
    }
}
