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
     *                   @OA\Property(property="name", type="string", example="Voluptas incidunt repellat animi. Sed ut beatae fugit ullam."),
     *                   @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ3"),
     *                   @OA\Property(property="client_id", type="string", example="w8CLyeP8vhnPK1V0mJ8ubU7UHCVnK7Bm"),
     *                   @OA\Property(property="logo", type="string", example="hhttps:\/\/via.placeholder.com\/640x480.png\/0044ee?text=animals+harum"),
     *                   @OA\Property(property="description", type="string", example="Magni minima facilis quo soluta. Ab quasi quaerat doloremque. Sapiente asperiores nisi maiores ex quia velit."),
     *                   @OA\Property(property="team_id", type="integer", example="1"),
     *                   @OA\Property(property="user_id", type="integer", example="2"),
     *                   @OA\Property(property="enabled", type="boolean", example="false"),
     *                   @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="tags", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="[]", @OA\Items()),
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
