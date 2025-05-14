<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Laravel\Pennant\Feature;
use App\Services\FeatureFlagManager;

class FeatureFlagController extends Controller
{
    /**
      * @OA\Post(
      *    path="/api/v1/feature-flags",
      *    operationId="define_feature_flags_from_github",
      *    tags={"Application"},
      *    summary="Define feature flags from GitHub or request body",
      *    description="Validates a bearer token, then either defines feature flags from the request body (JSON), or fetches them from GitHub using a configured URL. Defines features using Laravel Pennant.",
      *    security={{"bearerAuth":{}}},
      *    @OA\RequestBody(
      *        required=false,
      *        @OA\JsonContent(
      *            type="object",
      *            example={
      *                "createDatasets": {
      *                    "enabled": true
      *                },
      *                "upload": {
      *                    "enabled": false
      *                },
      *                "gmi": {
      *                    "enabled": true,
      *                    "features": {
      *                        "auth": {
      *                            "enabled": true
      *                        },
      *                        "no-auth": {
      *                            "enabled": false
      *                        }
      *                    }
      *                }
      *            }
      *        )
      *    ),
      *    @OA\Response(
      *        response=200,
      *        description="Success",
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
      *        description="Failed to fetch flags",
      *        @OA\JsonContent(
      *            @OA\Property(property="message", type="string", example="Failed to fetch feature flags.")
      *        )
      *    )
      * )
      */
    public function index(Request $request, FeatureFlagManager $flagManager): JsonResponse
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

        $featureFlags = $request->json()->all();

        if (!empty($featureFlags)) {
            Log::info("Using feature flags from request body: " . print_r($featureFlags, true));
        } else {
            $url = env('FEATURE_FLAGGING_CONFIG_URL');

            if (app()->environment('testing') || !$url) {
                return response()->json(['message' => 'Feature flagging disabled in this environment.'], 200);
            }

            $res = Http::get($url);

            if (!$res->successful()) {
                Log::error('Failed to fetch feature flags from GitHub', ['url' => $url]);
                return response()->json(['message' => 'Failed to fetch feature flags.'], 500);
            }

            $featureFlags = $res->json();

            if (!is_array($featureFlags)) {
                return response()->json(['message' => 'Invalid feature flag format.'], 422);
            }

            Log::info("Using feature flags from GitHub: " . print_r($featureFlags, true));
        }

        $flagManager->define($featureFlags);

        return response()->json(['message' => 'Feature flags defined successfully.'], 200);
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
    public function getEnabledFeatures(Request $request, FeatureFlagManager $flagManager): JsonResponse
    {
        $allFlags = $flagManager->getAllFlags();
        $enabledFlags = [];

        foreach ($allFlags as $flag) {
            if (Feature::active($flag)) {
                $enabledFlags[] = $flag;
            }
        }

        return response()->json(['enabled_features' => $enabledFlags], 200);
    }
}
