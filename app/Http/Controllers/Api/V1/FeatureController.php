<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class FeatureController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/features",
     *   operationId="FeatureIndex",
     *   tags={"Feature"},
     *   summary="List feature flags and their resolved values (global scope)",
     *   description="Returns a key/value map of feature names to their resolved values for the global (null) scope.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         description="Map of feature name to resolved value",
     *         additionalProperties=@OA\Schema(
     *           oneOf={
     *
     *             @OA\Schema(type="boolean"),
     *             @OA\Schema(type="string"),
     *             @OA\Schema(type="integer"),
     *             @OA\Schema(type="number"),
     *             @OA\Schema(type="array"),
     *             @OA\Schema(type="object")
     *           }
     *         ),
     *         example={
     *           "new-checkout"=true,
     *           "beta-dashboard"=false
     *         }
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {

        $driver = $this->pennantDriver();
        if ($driver !== 'database') {
            return response()->json([
                'message' => 'This endpoint requires PENNANT_STORE=database because it lists features from the database store.',
                'data' => [],
            ], 501);
        }

        $names = \DB::table('features')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        //this will allow us to control user specific flags if need be
        $user = $request->user();
        $values = Feature::for($user)->values($names);

        return response()->json([
            'data' => $values,
        ], 200);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/features/{name}",
     *   operationId="FeatureToggleByName",
     *   tags={"Feature"},
     *   summary="Toggle a feature flag by name",
     *   description="Toggles the global (null-scope) value of a feature flag by name and returns the resolved active state for the authenticated user.",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="name",
     *     in="path",
     *     required=true,
     *     description="Feature flag name",
     *
     *     @OA\Schema(type="string", example="new-checkout")
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(
     *         property="data",
     *         type="boolean",
     *         description="Resolved active state for the authenticated user after toggling",
     *         example=true
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=404,
     *     description="Feature not found",
     *
     *     @OA\JsonContent(
     *       type="object",
     *
     *       @OA\Property(property="message", type="string", example="not found")
     *     )
     *   )
     * )
     */
    public function toggleByName(Request $request, string $name)
    {

        $driver = $this->pennantDriver();
        if ($driver !== 'database') {
            return response()->json([
                'message' => 'This endpoint requires PENNANT_STORE=database to toggle stored feature flags.',
            ], 501);
        }

        $user = $request->user();
        $exists = \DB::table('features')
            ->where('name', $name)
            ->exists();

        if (! $exists) {
            throw new NotFoundException;
        }

        if (Feature::active($name)) {
            Feature::deactivate($name);
        } else {
            Feature::activate($name);
        }

        Feature::flushCache();

        return response()->json([
            'data' => Feature::for($user)->active($name),
        ], 200);
    }

    // Hide from swagger docs
    public function flushAllFeatures(Request $request)
    {
        Feature::flushCache();

        return response()->json([
            'message' => 'Feature cache flushed successfully.',
        ], 200);

    }

    private function pennantDriver(): string
    {
        $storeName = config('pennant.default', 'array');

        return config("pennant.stores.$storeName.driver", 'array');
    }
}
