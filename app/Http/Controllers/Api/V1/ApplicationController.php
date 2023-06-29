<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ApplicationController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/applications",
     *    operationId="fetch_all_applications",
     *    tags={"Application"},
     *    summary="ApplicationController@index",
     *    description="Returns a list of applications",
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="expedita"),
     *                   @OA\Property(property="description", type="string", example="Quibusdam in ducimus eos est."),
     *                   @OA\Property(property="image_link", type="string", example="https:\/\/via.placeholder.com\/640x480.png\/003333?text=animals+iusto"),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="keywords", type="string", example="minus deserunt dolorum"),
     *                   @OA\Property(property="public", type="boolean", example="0"),
     *                   @OA\Property(property="counter", type="integer", example="34319"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                )
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function index(): JsonResponse
    {
        $applications = Application::with(['permissions', 'tags', 'team', 'user'])->paginate(Config::get('constants.per_page'));

        return response()->json(
            $applications
        );
    }

    // public function show(Request $request, int $id): JsonResponse
    // {
    //     //
    // }

    // public function store(Request $request): JsonResponse
    // {
    //     //
    // }

    // public function update(Request $request, int $id): JsonResponse
    // {
    //    //
    // }

    // public function edit(Request $request, int $id): JsonResponse
    // {
    //     //
    // }

    // public function destroy(Request $request, int $id): JsonResponse
    // {
    //     //
    // }
}
