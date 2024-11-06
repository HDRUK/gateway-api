<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Auditor;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;

use App\Models\Dataset;

class AdminDatasetController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/datasets/admin_ctrl/trigger/term_extraction",
     *     summary="Trigger Term Extraction for Datasets",
     *     description="Triggers the term extraction job for datasets within a specified range and controls whether data is partially indexed in Elasticsearch.",
     *     tags={"Datasets"},
     *     security={{"jwt": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="partial",
     *                 type="boolean",
     *                 default=true,
     *                 description="Flag to determine if term extraction should be partial (true) or full (false)"
     *             ),
     *             @OA\Property(
     *                 property="minId",
     *                 type="integer",
     *                 default=1,
     *                 description="Minimum dataset ID to include in the term extraction"
     *             ),
     *             @OA\Property(
     *                 property="maxId",
     *                 type="integer",
     *                 description="Maximum dataset ID to include in the term extraction. Defaults to the maximum dataset ID available."
     *             ),
     *             @OA\Property(
     *                 property="indexElastic",
     *                 type="boolean",
     *                 default=true,
     *                 description="Flag to determine if data should be indexed in Elasticsearch"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Term extraction triggered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="triggered ted"),
     *             @OA\Property(property="dataset_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="An error message detailing the issue")
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="JWT token for authorization in the format 'Bearer {token}'"
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Role required to access this endpoint, e.g., 'hdruk.superadmin'"
     *     )
     * )
     */
    public function triggerTermExtraction(Request $request): JsonResponse
    {
        try {

            if(!Config::get('ted.enabled')) {
                throw new Exception("TED not enabled and you're trying to trigger TED");
            }

            $partial = $request->input('partial', Config::get('ted.use_partial'));
            $minId = $request->input('minId', 1);
            $maxId = $request->input('maxId', Dataset::max('id'));
            $elasticIndexing = $request->input('indexElastic', true);

            $datasetIds = Dataset::whereBetween("id", [$minId, $maxId])
                            ->select('id')
                            ->pluck('id');

            foreach ($datasetIds as $id) {
                $dataset = Dataset::where('id', $id)->first();
                $latestMetadata = $dataset->lastMetadata();
                $datasetVersionId = $dataset->latestVersionId($id);
                $versionNumber = $dataset->lastMetadataVersionNumber()->version;

                $tedData = $partial ? $latestMetadata['metadata']['summary'] : $latestMetadata['metadata'];

                TermExtraction::dispatch(
                    $id,
                    $datasetVersionId,
                    $versionNumber,
                    base64_encode(gzcompress(gzencode(json_encode($tedData)))),
                    $elasticIndexing,
                    $partial,
                );
            }
            return response()->json([
                'message' => "triggered ted",
                "dataset_ids" => $datasetIds,
                "used_partial" => $partial
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
    /**
     * @OA\Post(
     *     path="/api/v1/datasets/admin_ctrl/trigger/linkage_extraction",
     *     summary="Trigger Term Extraction for Datasets",
     *     description="Triggers the term extraction job for datasets within a specified range and controls whether data is partially indexed in Elasticsearch.",
     *     tags={"Datasets"},
     *     security={{"jwt": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="minId",
     *                 type="integer",
     *                 default=1,
     *                 description="Minimum dataset ID to include in the term extraction"
     *             ),
     *             @OA\Property(
     *                 property="maxId",
     *                 type="integer",
     *                 description="Maximum dataset ID to include in the term extraction. Defaults to the maximum dataset ID available."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Linkage extraction triggered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="triggered linkage"),
     *             @OA\Property(property="dataset_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="An error message detailing the issue")
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="JWT token for authorization in the format 'Bearer {token}'"
     *     )
     * )
     */
    public function triggerLinkageExtraction(Request $request): JsonResponse
    {
        try {

            $minId = $request->input('minId', 1);
            $maxId = $request->input('maxId', Dataset::max('id'));

            $datasetIds = Dataset::whereBetween("id", [$minId, $maxId])
                            ->select('id')
                            ->pluck('id');

            foreach ($datasetIds as $id) {
                $dataset = Dataset::where('id', $id)->first();
                $latestMetadata = $dataset->lastMetadata();
                $datasetVersionId = $dataset->latestVersionId($id);

                LinkageExtraction::dispatch(
                    $dataset->id,
                    $datasetVersionId
                );
            }
            return response()->json([
                'message' => "triggered linkage",
                "dataset_ids" => $datasetIds,
            ], 200);

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
