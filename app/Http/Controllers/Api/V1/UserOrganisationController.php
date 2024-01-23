<?php

namespace App\Http\Controllers\Api\V1;

use Exception;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

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

                    return response()->json([
                        'message' => 'success',
                        'data' => [
                            'organisations' => $organisations
                        ]
                    ], 200);
                }
                
                return response()->json([
                    'message' => 'forbidden',
                    'details' => 'you must be an admin or cohort admin to perform this action'
                ], 403);
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
