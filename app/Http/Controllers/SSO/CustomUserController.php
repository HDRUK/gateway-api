<?php

namespace App\Http\Controllers\SSO;

use Exception;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\CohortRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CohortRequestHasPermission;

class CustomUserController extends Controller
{
    /**
     * constructor
     */
    public function __constructor()
    {
        //
    }

    public function userInfo(Request $request)
    {
        try {
            $user = Auth::user();

            $profile = [
                $user->firstname,
                $user->lastname,
            ];

            $userId = $user->id;

            $cohortRequest = CohortRequest::where([
                'user_id' => $userId,
                'request_status' => 'APPROVED',
            ])->first();

            if (!$cohortRequest) {
                return [];
            }

            $cohortRequestRoleIds = CohortRequestHasPermission::select('permission_id')->where([
                'cohort_request_id' => $cohortRequest->id
            ])->get()->toArray();

            $crRoleIds = [];
            foreach ($cohortRequestRoleIds as $cohortRequestRoleId) {
                $crRoleIds[] = $cohortRequestRoleId['permission_id'];
            }

            $rquestrRoles = Permission::select('name')->whereIn('id', $crRoleIds)->get()->toArray();
            $rRoles = [];
            foreach ($rquestrRoles as $rquestrRole) {
                $rRoles[] = $rquestrRole['name'];
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'preferred_username' => $user->name,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'profile' => $profile,
                'given_name' => $user->firstname,
                'family_name' => $user->lastname,
                'email' => ($user->provider === 'open-athens' || $user->preferred_email === 'secondary') ? $user->secondary_email : $user->email,
                'rquestroles' => $rRoles,
            ]);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}
