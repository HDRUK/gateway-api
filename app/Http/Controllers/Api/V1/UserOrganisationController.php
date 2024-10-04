<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Auditor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class UserOrganisationController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/users/organisations",
     *      summary="Return a distinct list of all organisations which users belong to",
     *      description="Return a distinct list of all organisations which users belong to",
     *      tags={"UserOrganisation"},
     *      summary="UserOrganisation@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array", @OA\Items())
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            if (count($jwtUser)) {
                $userIsAdmin = (bool) $jwtUser['is_admin'];

                $user = User::where('id', $jwtUser['id'])->with('roles')->first();
                $roleNames = array();
                foreach ($user->roles as $role) {
                    $roleNames[] = $role['name'];
                }
                $userIsCohortAdmin = in_array('hdruk.cohort.admin', $roleNames);

                if ($userIsAdmin || $userIsCohortAdmin) {

                    $organisations = User::select('organisation')
                        ->distinct()
                        ->pluck('organisation')
                        ->toArray();

                    Auditor::log([
                        'user_id' => (int)$jwtUser['id'],
                        'action_type' => 'GET',
                        'action_name' => class_basename($this) . '@' . __FUNCTION__,
                        'description' => 'User Organisation get all',
                    ]);

                    return response()->json([
                        'message' => 'success',
                        'data' => $organisations
                    ], 200);
                }
            }

            return response()->json([
                'message' => 'forbidden',
                'details' => 'you must be an admin or cohort admin to perform this action'
            ], 403);

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
