<?php

namespace App\Http\Controllers\Api\V1;

use Js;
use Config;
use Exception;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\ApplicationHasTag;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\ApplicationHasPermission;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\Application\GetApplication;
use App\Http\Requests\Application\EditApplication;
use App\Http\Requests\Application\CreateApplication;
use App\Http\Requests\Application\UpdateApplication;

class ApplicationController extends Controller
{
    use RequestTransformation;
    
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
     *                   @OA\Property(property="image_link", type="string", example="hhttps:\/\/via.placeholder.com\/640x480.png\/0044ee?text=animals+harum"),
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

    /**
     * @OA\Get(
     *    path="/api/v1/applications/{id}",
     *    operationId="fetch_applications",
     *    tags={"Application"},
     *    summary="ApplicationController@show",
     *    description="Get application by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="Voluptas incidunt repellat animi. Sed ut beatae fugit ullam."),
     *                   @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ3"),
     *                   @OA\Property(property="client_id", type="string", example="w8CLyeP8vhnPK1V0mJ8ubU7UHCVnK7Bm"),
     *                   @OA\Property(property="image_link", type="string", example="hhttps:\/\/via.placeholder.com\/640x480.png\/0044ee?text=animals+harum"),
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
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function show(GetApplication $request, int $id): JsonResponse
    {
        try {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Application::with(['permissions', 'tags', 'team', 'user'])->where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/applications",
     *    summary="Create application",
     *    description="Creates application",
     *    tags={"Application"},
     *    summary="ApplicationController@store",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *        required=true,
     *        description="Application definition",
     *        @OA\JsonContent(
     *            required={"name", "app_id", "client_id", "image_link", "description", "team_id", "user_id", "enabled", "tags", "permissions"},
     *            @OA\Property(property="name", type="string", example="Corrupti in a voluptas. Eligendi saepe sed sit."),
     *            @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ4"),
     *            @OA\Property(property="client_id", type="string", example="iem4i3geb1FxehvvQBlSOZ2A6S6digs"),
     *            @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam"),
     *            @OA\Property(property="description", type="string", example="Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. "),
     *            @OA\Property(property="team_id", type="integer", example="1"),
     *            @OA\Property(property="user_id", type="integer", example="2"),
     *            @OA\Property(property="enabled", type="boolean", example="false"),
     *            @OA\Property(property="tags", type="array", example="[]", @OA\Items()),
     *            @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="data", type="integer", example="100")
     *        ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error")
     *        )
     *    )
     * )
     */
    public function store(CreateApplication $request): JsonResponse
    {
        try {
            $input = $request->all();

            $application = Application::create([
                'name' => $input['name'],
                'app_id' => $input['app_id'],
                'client_id' => $input['client_id'],
                'image_link' => $input['image_link'],
                'description' => $input['description'],
                'team_id' => $input['team_id'],
                'user_id' => $input['user_id'],
                'enabled' => $input['enabled'],
            ]);

            $this->applicationHasTags((int) $application->id, $input['tags']);
            $this->applicationHasPermissions((int) $application->id, $input['permissions']);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $application->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/applications/{id}",
     *    tags={"Application"},
     *    summary="Update application",
     *    description="Update application",
     *    summary="ApplicationController@update",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *        required=true,
     *        description="ActivityLog definition",
     *        @OA\JsonContent(
     *            required={"name", "app_id", "client_id", "image_link", "description", "team_id", "user_id", "enabled", "tags", "permissions"},
     *            @OA\Property(property="name", type="string", example="Corrupti in a voluptas. Eligendi saepe sed sit."),
     *            @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ4"),
     *            @OA\Property(property="client_id", type="string", example="iem4i3geb1FxehvvQBlSOZ2A6S6digs"),
     *            @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam"),
     *            @OA\Property(property="description", type="string", example="Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. "),
     *            @OA\Property(property="team_id", type="integer", example="1"),
     *            @OA\Property(property="user_id", type="integer", example="2"),
     *            @OA\Property(property="enabled", type="boolean", example="false"),
     *            @OA\Property(property="tags", type="array", example="[]", @OA\Items()),
     *            @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *        ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="covid"),
     *                 @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *                 @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *                 @OA\Property(property="enabled", type="boolean", example="true"),
     *                 @OA\Property(property="keywords", type="string", example="key words"),
     *                 @OA\Property(property="public", type="boolean", example="true"),
     *                 @OA\Property(property="counter", type="integer", example="123"),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *              ),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error")
     *        )
     *    )
     * )
     */
    public function update(UpdateApplication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            Application::where('id', $id)->update([
                'name' => $input['name'],
                'app_id' => $input['app_id'],
                'client_id' => $input['client_id'],
                'image_link' => $input['image_link'],
                'description' => $input['description'],
                'team_id' => $input['team_id'],
                'user_id' => $input['user_id'],
                'enabled' => $input['enabled'],
            ]);

            $this->applicationHasTags((int) $id, $input['tags']);
            $this->applicationHasPermissions((int) $id, $input['permissions']);

            return response()->json([
                'message' => 'success',
                'data' => Application::with(['permissions', 'tags', 'team', 'user'])->where('id', $id)->first()
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/applications/{id}",
     *    tags={"Application"},
     *    summary="Edit application",
     *    description="Edit application",
     *    summary="ApplicationController@edit",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *        required=true,
     *        description="ActivityLog definition",
     *        @OA\JsonContent(
     *            @OA\Property(property="name", type="string", example="Corrupti in a voluptas. Eligendi saepe sed sit."),
     *            @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ4"),
     *            @OA\Property(property="client_id", type="string", example="iem4i3geb1FxehvvQBlSOZ2A6S6digs"),
     *            @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam"),
     *            @OA\Property(property="description", type="string", example="Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. "),
     *            @OA\Property(property="team_id", type="integer", example="1"),
     *            @OA\Property(property="user_id", type="integer", example="2"),
     *            @OA\Property(property="enabled", type="boolean", example="false"),
     *            @OA\Property(property="tags", type="array", example="[]", @OA\Items()),
     *            @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *        ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="covid"),
     *                 @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *                 @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *                 @OA\Property(property="enabled", type="boolean", example="true"),
     *                 @OA\Property(property="keywords", type="string", example="key words"),
     *                 @OA\Property(property="public", type="boolean", example="true"),
     *                 @OA\Property(property="counter", type="integer", example="123"),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *              ),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error")
     *        )
     *    )
     * )
     */
    public function edit(EditApplication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            $arrayKeys = ['name', 'app_id', 'client_id', 'image_link', 'description', 'team_id', 'user_id', 'enabled'];
            $array = $this->checkEditArray($input, $arrayKeys);

            Application::where('id', $id)->update($array);

            if (array_key_exists('tags', $input)) {
                $this->applicationHasTags((int) $id, $input['tags']);
            }

            if (array_key_exists('permissions', $input)) {
                $this->applicationHasPermissions((int) $id, $input['permissions']);
            }
            
            return response()->json([
                'message' => 'success',
                'data' => Application::with(['permissions', 'tags', 'team', 'user'])->where('id', $id)->first()
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // public function destroy(Request $request, int $id): JsonResponse
    // {
    //     //
    // }

    /**
     * Application has tags associated
     *
     * @param integer $applicationId
     * @param array $tags
     * @return mixed
     */
    private function applicationHasTags(int $applicationId, array $tags): mixed
    {
        try {
            ApplicationHasTag::where('application_id', $applicationId)->delete();
            foreach ($tags as $tag) {
                ApplicationHasTag::create([
                    'application_id' => $applicationId,
                    'tag_id' => $tag,
                ]);
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Application has permissions associated
     *
     * @param integer $applicationId
     * @param array $permissions
     * @return mixed
     */
    private function applicationHasPermissions(int $applicationId, array $permissions): mixed
    {
        try {
            ApplicationHasPermission::where('application_id', $applicationId)->delete();
            foreach ($permissions as $permission) {
                ApplicationHasPermission::create([
                    'application_id' => $applicationId,
                    'permission_id' => $permission,
                ]);
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
