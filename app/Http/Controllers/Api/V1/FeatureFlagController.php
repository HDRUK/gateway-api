<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Laravel\Pennant\Feature;
use Illuminate\Support\Facades\Log;

class FeatureFlagController extends Controller
{
    /**
 * @OA\Post(
 *    path="/api/v1/feature-flags",
 *    operationId="define_feature_flags",
 *    tags={"Application"},
 *    summary="Define feature flags dynamically",
 *    description="Accepts a nested feature flag payload and defines feature flags dynamically using Laravel Pennant",
 *    @OA\RequestBody(
 *        required=true,
 *        @OA\JsonContent(
 *            @OA\Property(
 *                property="features",
 *                type="object",
 *                example={
 *                    "features.SDEConciergeServiceEnquiry": {"enabled": true},
 *                    "features.experimentalGroup": {
 *                        "enabled": false,
 *                        "betaFlag": {"enabled": true}
 *                    }
 *                }
 *            )
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
 *        response=422,
 *        description="Invalid input",
 *        @OA\JsonContent(
 *            @OA\Property(property="message", type="string", example="Invalid feature flag format.")
 *        )
 *    )
 * )
 */
    public function store(Request $request): JsonResponse
    {
        $features = $request->input('features');

        if (!is_array($features)) {
            return response()->json(['message' => 'Invalid feature flag format.'], 422);
        }

        $this->defineFeatureFlags($features);

        return response()->json(['message' => 'Feature flags defined successfully.']);
    }

    protected function defineFeatureFlags(array $flags, string $prefix = ''): void
    {
        foreach ($flags as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (isset($value['enabled']) && is_bool($value['enabled'])) {
                    Feature::define($fullKey, $value['enabled']);
                    Log::info("Feature flag defined: {$fullKey} = " . ($value['enabled'] ? 'ENABLED' : 'DISABLED'));
                }

                foreach ($value as $subKey => $subVal) {
                    if (is_array($subVal)) {
                        $this->defineFeatureFlags([$subKey => $subVal], $fullKey);
                    }
                }
            }
        }
    }
}
