<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\Dataset;
use InvalidArgumentException;
use Laravel\Pennant\Feature;
use App\Http\Traits\CheckAccess;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\OnboardingForm\OnboardingFormService;
use App\Http\Requests\V2\FormHydration\GetFormHydration;

class FormHydrationController extends Controller
{
    use CheckAccess;

    public function __construct(
        private readonly OnboardingFormService $onboardingFormService,
    ) {
    }

    /**
     * @OA\Get(
     *      x={"internal"="true"},
     *    path="/api/v2/teams/{teamId}/form_hydration",
     *    operationId="fetch_team_form_hydration_v2",
     *    tags={"Form Hydration"},
     *    summary="FormHydrationController@show",
     *    description="Returns the data the metadata onboarding form is generated from: the JSON Schema of the selected input schema, the input schemas on offer, the team's default values and, when editing, the dataset's metadata in the selected schema. Contains no UI fields. Behind the SchemaOnboardingForm flag; form hydration is actively being reworked.",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="model",
     *       in="query",
     *       description="Input schema model, one of those listed in schemas. Defaults to the dataset's source schema or the partner default.",
     *       @OA\Schema(type="string", example="HDRUK")
     *    ),
     *    @OA\Parameter(
     *       name="version",
     *       in="query",
     *       description="Input schema version, required with model.",
     *       @OA\Schema(type="string", example="4.1.0")
     *    ),
     *    @OA\Parameter(
     *       name="dataset_id",
     *       in="query",
     *       description="The team's dataset being edited. Adds metadata to the response.",
     *       @OA\Schema(type="integer")
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="object",
     *             @OA\Property(property="schemas", type="array", @OA\Items(type="object",
     *                @OA\Property(property="model", type="string", example="HDRUK"),
     *                @OA\Property(property="version", type="string", example="4.1.0"),
     *             )),
     *             @OA\Property(property="default", type="object",
     *                @OA\Property(property="model", type="string", example="HDRUK"),
     *                @OA\Property(property="version", type="string", example="4.1.0"),
     *             ),
     *             @OA\Property(property="model", type="string", example="HDRUK"),
     *             @OA\Property(property="version", type="string", example="4.1.0"),
     *             @OA\Property(property="schema", type="object", description="JSON Schema of the selected input schema"),
     *             @OA\Property(property="defaultValues", type="object", description="Team defaults in the selected schema"),
     *             @OA\Property(property="metadata", type="object", nullable=true, description="Only with dataset_id"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="The requested schema is not offered",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *       )
     *    )
     * )
     */
    public function show(GetFormHydration $request, int $teamId): JsonResponse
    {
        if (!Feature::active('SchemaOnboardingForm')) {
            return response()->json([
                'message' => 'Resource not found',
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        }

        $this->checkAccess($request->all(), $teamId, null, 'team', $request->header());
        $team = Team::findOrFail($teamId);

        try {
            $dataset = null;
            if ($request->filled('dataset_id')) {
                $dataset = Dataset::where('team_id', $teamId)->find($request->integer('dataset_id'));
                if (!$dataset) {
                    return response()->json([
                        'message' => 'Dataset not found',
                    ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
                }
            }

            $data = $this->onboardingFormService->hydrate(
                $team,
                $dataset,
                $request->query('model'),
                $request->query('version'),
            );

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team ' . $teamId . ' form hydration get',
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $data,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
