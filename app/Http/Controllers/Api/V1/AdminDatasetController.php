<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Auditor;

use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\TermExtraction;

use App\Models\Dataset;

class AdminDatasetController extends Controller
{
    //protected boolean $allTerms = false;

    public function triggerTermExtraction(Request $request): JsonResponse
    {
        try {
            $partial = $request->input('partial', true);
            $minId = $request->input('minId', 1);
            $maxId = $request->input('maxId', Dataset::max('id'));

            $datasetIds = Dataset::whereBetween("id", [$minId, $maxId])
                            ->select('id')
                            ->pluck('id');

            foreach ($datasetIds as $id) {
                $dataset = Dataset::where('id', $id)->first();
                $latestMetadata = $dataset->lastMetadata();
                $datasetVersionId = $dataset->latestVersionId($id);
                $versionNumber = $dataset->lastMetadataVersionNumber()->version;
                $elasticIndexing = true;

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
            return response()->json(['message' => "triggered ted","datasetIds" => $datasetIds], 200);
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
