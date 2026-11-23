<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\VariableSearch as VariableSearchRequest;
use App\Services\Search\VariableIndex;
use App\Services\Search\VariableSearch;
use Illuminate\Http\JsonResponse;

class VariableSearchController extends Controller
{
    public function __construct(protected VariableSearch $variables)
    {
    }

    /**
     * Datasets whose structural metadata holds variables matching the terms.
     * With dataset_ids, returns just those datasets (for comparing them).
     */
    public function search(VariableSearchRequest $request): JsonResponse
    {
        if (! VariableIndex::enabled()) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $terms = array_map(fn ($term) => trim((string) $term), (array) $request->input('terms', []));
        $datasetIds = $request->has('dataset_ids') ? (array) $request->input('dataset_ids') : null;

        return response()->json([
            'message' => 'success',
            'data' => $this->variables->search(
                $terms,
                $request->input('match') ?? VariableSearch::MATCH_ALL,
                $datasetIds,
            ),
        ]);
    }
}
