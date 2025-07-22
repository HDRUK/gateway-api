<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Laravel\Pennant\Feature;
use App\Services\FeatureFlagManager;

class FeatureFlagController extends Controller
{
    /**
      * @OA\Get(
      *    path="/api/v1/feature-flags/enabled",
      *    operationId="get_enabled_feature_flags",
      *    tags={"Application"},
      *    summary="Get all currently enabled feature flags",
      *    description="Returns a list of currently enabled feature flags for the application.",
      *    @OA\Response(
      *        response=200,
      *        description="List of enabled feature flags",
      *        @OA\JsonContent(
      *            @OA\Property(property="enabled_features", type="array", @OA\Items(type="string"))
      *        )
      *    )
      * )
      */
    public function getEnabledFeatures(Request $request, FeatureFlagManager $flagManager): JsonResponse
    {
        $allFlags = $flagManager->getAllFlags();

        return response()->json($allFlags, 200);
    }
}
