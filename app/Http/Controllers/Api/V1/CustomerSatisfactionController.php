<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomerSatisfaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;
use Config;

class CustomerSatisfactionController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/v1/csat",
     *      tags={"CustomerSatisfaction"},
     *      summary="Create Customer Satisfaction Score",
     *      description="Creates a customer satisfaction score between 0 and 5",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Customer Satisfaction score",
     *          @OA\JsonContent(
     *              required={"score"},
     *              @OA\Property(property="score", type="integer", example=1),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Resource Created",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="id", type="integer", example=123),
     *              @OA\Property(property="errors", type="object"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal Server Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          ),
     *      )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'score' => 'required|integer|min:0|max:5',
            ]);

            $csat = CustomerSatisfaction::create([
                'score' => $validated['score']
            ]);

            return response()->json([
                'data' => [
                'id' => $csat->id,
                ]

            ], Config::get('statuscodes.STATUS_CREATED.code'));

        } catch (Exception $e) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
                'error' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/csat/{id}",
     *      tags={"CustomerSatisfaction"},
     *      summary="Update Customer Satisfaction Description",
     *      description="Update a description for a satisfaction score entry",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="ID of the CSAT entry",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Reason to update",
     *          @OA\JsonContent(
     *              required={"reason", "score"},
     *              @OA\Property(property="reason", type="string", example="Your feedback goes here..."),
     *              @OA\Property(property="score", type="integer", example=1),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Update successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="reason updated")
     *          )
     *      )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
                'score' => 'required|integer|min:0|max:5',
            ]);

            $csat = CustomerSatisfaction::findOrFail($id);
            $csat->reason = $validated['reason'];
            $csat->score = $validated['score'];
            $csat->save();

            return response()->json([
                'message' => 'Survey updated',
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
                'error' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        }
    }
}
