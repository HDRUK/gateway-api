<?php

namespace App\Http\Controllers\Api\V1;

use Exception;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{

    /**
     * @OA\Get(
     *      path="/api/v1/search",
     *      summary="Keyword search across multiple gateway entity types",
     *      description="Returns gateway entities related to the provided query term(s)",
     *      tags={"Search"},
     *      summary="Search@search",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Submit search query",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="query", type="string", example="asthma dataset"),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="datasets", type="array", @OA\Items()),
     *                      @OA\Property(property="tools", type="array", @OA\Items()),
     *                      @OA\Property(property="collections", type="array", @OA\Items()),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $urlString = env('SEARCH_SERVICE_URL') . '/search';

            $response = Http::withBody(
                $request->getContent(), 'application/json'
            )->get($urlString);

            return response()->json([
                'message' => 'success',
                'data' => $response->json()
            ], 200);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
