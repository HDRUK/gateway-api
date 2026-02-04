<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Models\Feature as FeatureModel;
use App\Http\Requests\Features\GetFeatureById;
use App\Http\Requests\Features\ToggleByFeatureId;

class FeatureController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/v1/features",
     *    summary="Return a list of Feature entries",
     *    description="Return a list of Feature entries",
     *    tags={"Feature"},
     *    summary="Feature@index",
     *    security={{"bearerAuth":{}}},
     *
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="data", type="object",
     *            @OA\Property(property="id", type="integer", example="123"),
     *             @OA\Property(property="name", type="string", example="feature"),
     *             @OA\Property(property="scope", type="string", example="\App\Model|User:1"),
     *             @OA\Property(property="value", type="boolean", example="true"),
     *             @OA\Property(property="description", type="string", example="description"),
     *             @OA\Property(property="created_at", type="string", example="2024-02-04 12:00:00"),
     *             @OA\Property(property="updated_at", type="string", example="2024-02-04 12:01:00"),
     *          )
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       )
     *    )
     * )
     */
    public function index(Request $request)
    {
        $features = FeatureModel::all();

        return response()->json([
            'data' => $features,
        ], 200);
    }

    /**
     * @OA\Get(
     *    path="/api/v1/features/{featureId}",
     *    summary="Return a Feature entry by its ID",
     *    description="Return a Feature entry by its ID",
     *    tags={"Feature"},
     *    summary="Feature@show",
     *    security={{"bearerAuth":{}}},
     *
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="data", type="object",
     *            @OA\Property(property="id", type="integer", example="123"),
     *             @OA\Property(property="name", type="string", example="feature"),
     *             @OA\Property(property="scope", type="string", example="\App\Model|User:1"),
     *             @OA\Property(property="value", type="boolean", example="true"),
     *             @OA\Property(property="description", type="string", example="description"),
     *             @OA\Property(property="created_at", type="string", example="2024-02-04 12:00:00"),
     *             @OA\Property(property="updated_at", type="string", example="2024-02-04 12:01:00"),
     *          )
     *       ),
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Invalid argument(s)",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Invalid argument(s)")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       )
     *    )
     * )
     */
    public function show(GetFeatureById $request, int $featureId)
    {
        $feature = FeatureModel::find($featureId);

        if (!$feature) {
            throw new NotFoundException();
        }


        return response()->json([
                 'data' => $feature,
             ], 200);
    }

    /**
     * @OA\Put(
     *    path="/api/v1/features/{featureId}",
     *    summary="Toggle and return a Feature entry by its ID",
     *    description="Toggle and return a Feature entry by its ID",
     *    tags={"Feature"},
     *    summary="Feature@show",
     *    security={{"bearerAuth":{}}},
     *
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="data", type="object",
     *            @OA\Property(property="id", type="integer", example="123"),
     *             @OA\Property(property="name", type="string", example="feature"),
     *             @OA\Property(property="scope", type="string", example="\App\Model|User:1"),
     *             @OA\Property(property="value", type="boolean", example="true"),
     *             @OA\Property(property="description", type="string", example="description"),
     *             @OA\Property(property="created_at", type="string", example="2024-02-04 12:00:00"),
     *             @OA\Property(property="updated_at", type="string", example="2024-02-04 12:01:00"),
     *          )
     *       ),
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Invalid argument(s)",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Invalid argument(s)")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       )
     *    )
     * )
     */
    public function toggleByFeatureId(ToggleByFeatureId $request, int $featureId)
    {

        $feature = FeatureModel::where('id', $featureId)->first();


        if (is_null($feature)) {
            throw new NotFoundException();
        }

        if (Feature::active($feature->name)) {
            Feature::deactivate($feature->name);
        } else {
            Feature::activate($feature->name);
        }

        Feature::flushCache();


        return response()->json([
                'data' => $feature,
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
}
