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
     *    summary="Fetch and define feature flags from GitHub",
     *    description="Validates a GitHub webhook signature, fetches a JSON config from the configured GitHub URL, and defines feature flags using Laravel Pennant.",
     *    @OA\Parameter(
     *        name="X-Hub-Signature-256",
     *        in="header",
     *        required=true,
     *        description="HMAC SHA-256 signature of the request body using the GitHub webhook secret",
     *        @OA\Schema(type="string")
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
     *        description="Unauthorized or invalid signature",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="Unauthorized: Signature mismatch.")
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
        $secret = env('GITHUB_WEBHOOK_FEATURE_FLAG_SECRET');
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature || !$secret) {
            return response()->json(['message' => 'Unauthorized: Missing signature or secret.'], 401);
        }

        $hash = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($hash, $signature)) {
            Log::warning('Invalid GitHub signature', ['provided' => $signature, 'calculated' => $hash]);
            return response()->json(['message' => 'Unauthorized: Signature mismatch.'], 401);
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

}
