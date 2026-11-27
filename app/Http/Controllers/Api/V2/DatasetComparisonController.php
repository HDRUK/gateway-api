<?php

namespace App\Http\Controllers\Api\V2;

use App\Context\PartnerContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dataset\CompareDatasets;
use App\Models\Dataset;
use App\Services\Comparison\DatasetComparison;
use App\Services\Comparison\FieldComparator;
use App\Services\DatasetService;
use Illuminate\Http\JsonResponse;

class DatasetComparisonController extends Controller
{
    public function __construct(
        private readonly DatasetService $datasets,
        private readonly PartnerContext $partnerContext,
    ) {
    }

    /**
     * Datasets' metadata side by side, with a similarity call for every field
     * of each against the first (see config/comparison.php). Each dataset's latest version is
     * compared; variables are left out (they have their own comparison:
     * POST /search/variables).
     *
     * @OA\Post(
     *      path="/api/v2/datasets/compare",
     *      operationId="compare_datasets_v2",
     *      summary="Compare datasets",
     *      description="Returns each dataset's metadata and, for each field, how alike each dataset is to the first: identical, similar, different, or missing when either has no value.",
     *      tags={"Datasets"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"dataset_ids"},
     *              @OA\Property(property="dataset_ids", type="array", @OA\Items(type="integer"), example={11, 12})
     *          )
     *      ),
     *      @OA\Response(response=200, description="Success"),
     *      @OA\Response(response=400, description="Not between 2 and comparison.max_datasets distinct dataset ids"),
     *      @OA\Response(response=404, description="A dataset is unknown, not active, or not visible to this partner")
     * )
     */
    public function compare(CompareDatasets $request): JsonResponse
    {
        $ids = array_map('intval', (array) $request->input('dataset_ids'));

        $found = Dataset::with('team')
            ->where('status', Dataset::STATUS_ACTIVE)
            ->forPartnerContext($this->partnerContext->getPartner())
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $missing = array_values(array_diff($ids, $found->keys()->all()));
        if ($missing !== []) {
            return response()->json([
                'message' => 'Dataset not found: '.implode(', ', $missing),
            ], 404);
        }

        // In the order asked for, so the first id is always the first column.
        $datasets = array_map(fn (int $id) => $this->describe($found[$id]), $ids);

        $comparison = new DatasetComparison(new FieldComparator(config('comparison.thresholds')));

        return response()->json([
            'message' => 'success',
            // The first dataset asked for is the reference the others are compared with.
            'data' => ['datasets' => $datasets] + $comparison->compare(...array_column($datasets, 'metadata')),
        ]);
    }

    /** One dataset's latest version, ready to render beside another. */
    private function describe(Dataset $dataset): array
    {
        $version = $dataset->latestVersion();
        $envelope = $this->datasets->getReconstructedMetadataEnvelope(
            $dataset->id,
            $version->version,
            false,
            $version,
        );

        $metadata = (array) ($envelope['metadata'] ?? []);
        // The variables are the other tab, and by far the largest part.
        unset($metadata['structuralMetadata']);

        return [
            'id' => $dataset->id,
            'title' => $version->title,
            'publisherName' => $dataset->team?->name,
            'gwdmVersion' => $version->gwdm_version,
            'metadata' => $metadata,
        ];
    }
}
