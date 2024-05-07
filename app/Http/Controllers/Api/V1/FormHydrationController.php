<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use MetadataManagementController as MMC;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Http;

class FormHydrationController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/form_hydration/schema",
     *      operationId="getFormSchema",
     *      tags={"Form Hydration"},
     *      summary="Retrieve form schema data",
     *      description="Retrieves form schema data based on the provided model and version.",
     *      @OA\Parameter(
     *          name="model",
     *          in="query",
     *          required=false,
     *          description="The model for which form schema is requested.",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="version",
     *          in="query",
     *          required=false,
     *          description="The version of the model for which form schema is requested.",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object"
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request. Missing required parameters or invalid parameters."
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error. Failed to retrieve form schema data."
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $model = $request->input('model',Config::get('form_hydration.schema.model'));
        $version = $request->input('version',Config::get('form_hydration.schema.latest_version'));

        $url = sprintf(Config::get('form_hydration.schema.url'), $model, $version);

        $response = Http::get($url);
        if ($response->successful()) {
            $payload = $response->json(); 
            return response()->json(["data"=>$payload]);
        } else {
             return response()->json([
                'message' => "Failed to retrieve form hydration from ".$url,
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }

    }

    public function onboardingFormHydration(Request $request): JsonResponse
    {
        $model = $request->input('model',Config::get('form_hydration.schema.model'));
        $version = $request->input('version',Config::get('form_hydration.schema.latest_version'));

        return MMC::getOnboardingFormHydrated($model, $version);
    }    
}
