<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Laravel\Pennant\Feature;
use App\FeatureFlags\FeatureFlagManager;

class FeatureFlagController extends Controller
{
    /**
 * @OA\Post(
 *    path="/api/v1/feature-flags",
 *    operationId="define_feature_flags_from_github",
 *    tags={"Application"},
 *    summary="Fetch and define feature flags from a remote URL (e.g., GitHub)",
 *    description="Validates a bearer token, then fetches feature flags from a configured remote URL and defines them using Laravel Pennant. If the URL is not set or environment is 'testing', the request is skipped.",
 *    security={{"bearerAuth":{}}},
 *    @OA\Response(
 *        response=200,
 *        description="Success or feature flagging skipped in the current environment",
 *        @OA\JsonContent(
 *            @OA\Property(property="message", type="string", example="Feature flags defined successfully.")
 *        )
 *    ),
 *    @OA\Response(
 *        response=401,
 *        description="Unauthorized or invalid token",
 *        @OA\JsonContent(
 *            @OA\Property(property="message", type="string", example="Unauthorized: Invalid token.")
 *        )
 *    ),
 *    @OA\Response(
 *        response=422,
 *        description="Invalid feature flag format",
 *        @OA\JsonContent(
 *            @OA\Property(property="message", type="string", example="Invalid feature flag format.")
 *        )
 *    ),
 *    @OA\Response(
 *        response=500,
 *        description="Failed to fetch feature flags",
 *        @OA\JsonContent(
 *            @OA\Property(property="message", type="string", example="Failed to fetch feature flags.")
 *        )
 *    )
 * )
 */
    public function reload(FeatureFlagManager $resolver): JsonResponse
    {
        $featureFlagToken = env('FEATURE_FLAG_API_TOKEN');
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized: Missing or invalid Authorization header.'], 401);
        }

        $providedToken = substr($authHeader, 7); // remove "Bearer "


        if ($providedToken !== $featureFlagToken) {
            Log::warning('Invalid API token', ['provided' => $providedToken]);
            return response()->json(['message' => 'Unauthorized: Invalid token.'], 401);
        }

        $resolver->reload();

        return response()->json([
            'message' => 'Feature flags reloaded from GCS.'
        ]);

    }
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
    public function getEnabledFeatures(FeatureFlagManager $resolver): JsonResponse
    {
        $allFlags = $resolver->getEnabledFeatures();

        return response()->json($allFlags, 200);
    }
}
