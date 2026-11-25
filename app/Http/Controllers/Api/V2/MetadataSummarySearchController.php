<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\MetadataSummarySearch as MetadataSummarySearchRequest;
use App\Services\Search\MetadataSummaryIndex;
use App\Services\Search\MetadataSummarySearch;
use Illuminate\Http\JsonResponse;

class MetadataSummarySearchController extends Controller
{
    public function __construct(protected MetadataSummarySearch $metadata_summary_search)
    {
    }

    /**
     * Datasets whose summary fields holds docs matching the terms.
     * With dataset_ids, returns just those datasets (for comparing them).
     */
    public function search(MetadataSummarySearchRequest $request): JsonResponse
    {
        if (! MetadataSummaryIndex::enabled()) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $terms = array_map(fn ($term) => trim((string) $term), (array) $request->input('terms', []));
        $datasetIds = $request->has('dataset_ids') ? (array) $request->input('dataset_ids') : null;

        return response()->json([
            'message' => 'success',
            'data' => $this->metadata_summary_search->search(
                $terms,
                $request->input('match') ?? MetadataSummarySearch::MATCH_ALL,
                $datasetIds,
            ),
        ]);
    }
}
