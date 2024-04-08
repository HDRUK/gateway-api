<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Config;
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
     *          required=true,
     *          description="The model for which form schema is requested.",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="version",
     *          in="query",
     *          required=true,
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
        $request->validate([
            'model' => 'required',
            'version' => 'required',
        ]);

        $model = $request->input('model');
        $version = $request->input('version');

        $url = sprintf(Config::get('form_hydration.schema.url'), $model, $version);

        $payload = Http::get($url)->json();

        return response()->json($payload);
    }
}
