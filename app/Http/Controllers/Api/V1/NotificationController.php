<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\EditNotification;
use App\Http\Requests\Notification\CreateNotification;
use App\Http\Requests\Notification\DeleteNotification;
use App\Http\Requests\Notification\UpdateNotification;
use App\Http\Traits\TeamTransformation;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\Notification\GetNotification;

class NotificationController extends Controller
{
    use TeamTransformation;
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/notifications",
     *      summary="List of notifications",
     *      description="Returns a list of notifications enabled on the system",
     *      tags={"Notification"},
     *      summary="Notification@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-19 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-19 12:00:00"),
     *                      @OA\Property(property="notification_type", type="string", example="someType"),
     *                      @OA\Property(property="message", type="string", example="some message"),
     *                      @OA\Property(property="opt_in", type="boolean", example="1"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $perPage = request('perPage', Config::get('constants.per_page'));
            $notifications = Notification::where('enabled', 1)->paginate($perPage);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Notification get all',
            ]);

            return response()->json(
                $notifications
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/notifications/{id}",
     *      summary="Return a single notification",
     *      description="Return a single notification",
     *      tags={"Notification"},
     *      summary="Notification@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="notification id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="notification id",
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="notification_type", type="string", example="someType"),
     *                  @OA\Property(property="message", type="string", example="some message"),
     *                  @OA\Property(property="opt_in", type="boolean", example="1"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="email", type="string", example="john@example.com"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function show(GetNotification $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $notification = Notification::findOrFail($id);

            // mask the email if the user_id is supplied. Otherwise, return the full email.
            if ($notification->user_id) {
                $user = User::where('id', $notification->user_id)->select('email', 'preferred_email', 'secondary_email')->first();
                $notification->email = $this->maskEmail($user->preferred_email === 'primary' ? $user->email : $user->secondary_email);
            }

            if ($notification) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $notification,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Notification get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/notifications",
     *      summary="Create a new notification",
     *      description="Creates a new notification",
     *      tags={"Notification"},
     *      summary="Notification@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Notification definition",
     *          @OA\JsonContent(
     *              required={"notification_type", "message", "opt_in", "enabled"},
     *              @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *              @OA\Property(property="message", type="string", example="your message here"),
     *              @OA\Property(property="opt_in", type="boolean", example="1"),
     *              @OA\Property(property="enabled", type="boolean", example="1"),
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example="100")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function store(CreateNotification $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $notification = Notification::create([
                'notification_type' => $input['notification_type'],
                'message' => $input['message'],
                'opt_in' => $input['opt_in'],
                'enabled' => $input['enabled'],
                'email' => isset($input['email']) ? $input['email'] : null,
                'user_id' => isset($input['email']) ? $input['user_id'] : null,
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Notification ' . $notification->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $notification->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/notifications/{id}",
     *      summary="Update a notification",
     *      description="Update a notification",
     *      tags={"Notification"},
     *      summary="Notification@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="notification id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="notification id",
     *         )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Notification definition",
     *          @OA\JsonContent(
     *              required={"notification_type", "message", "opt_in", "enabled"},
     *              @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *              @OA\Property(property="message", type="string", example="your message here"),
     *              @OA\Property(property="opt_in", type="boolean", example="1"),
     *              @OA\Property(property="enabled", type="boolean", example="1"),
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *                  @OA\Property(property="message", type="string", example="your message here"),
     *                  @OA\Property(property="opt_in", type="boolean", example="1"),
     *                  @OA\Property(property="email", type="string", example="john@example.com"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function update(UpdateNotification $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            Notification::where('id', $id)->update([
                'notification_type' => $input['notification_type'],
                'message' => $input['message'],
                'opt_in' => $input['opt_in'],
                'enabled' => $input['enabled'],
                'email' => $input['email'],
                'user_id' => $input['user_id'],
            ]);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Notification ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Notification::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/notifications/{id}",
     *      summary="Edit a notification",
     *      description="Edit a notification",
     *      tags={"Notification"},
     *      summary="Notification@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="notification id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="notification id",
     *         )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Notification definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *              @OA\Property(property="message", type="string", example="your message here"),
     *              @OA\Property(property="opt_in", type="boolean", example="1"),
     *              @OA\Property(property="enabled", type="boolean", example="1"),
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *                  @OA\Property(property="message", type="string", example="your message here"),
     *                  @OA\Property(property="opt_in", type="boolean", example="1"),
     *                  @OA\Property(property="email", type="string", example="john@example.com"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function edit(EditNotification $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $arrayKeys = [
                'notification_type',
                'message',
                'opt_in',
                'enabled',
                'email',
                'user_id',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Notification::where('id', $id)->update($array);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Notification ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Notification::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/notifications/{id}",
     *      summary="Delete a notification",
     *      description="Delete a notification",
     *      tags={"Notification"},
     *      summary="Notification@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="notification id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="notification id",
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *           ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function destroy(DeleteNotification $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $notification = Notification::findOrFail($id);
            if ($notification) {
                $notification->delete();

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'DELETE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => 'Notification ' . $id . ' deleted',
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
