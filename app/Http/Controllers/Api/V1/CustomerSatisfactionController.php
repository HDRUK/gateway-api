<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomerSatisfaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;

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
     *              @OA\Property(property="errors", type="object"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal Server Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'score' => 'required|integer|min:0|max:5',
            ]);

            CustomerSatisfaction::create([
                'score' => $validated['score']
            ]);
            return response()->json(['message' => 'Score saved successfully'], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
