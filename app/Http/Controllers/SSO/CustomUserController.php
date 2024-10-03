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
    
            $cohortRequestRoleIds = CohortRequestHasPermission::where([
                'cohort_request_id' => $cohortRequest->id
            ])->pluck('permission_id')->toArray();
    
            $rquestrRoles = Permission::whereIn('id', $cohortRequestRoleIds)->pluck('name')->toArray();

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'preferred_username' => $user->name,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'profile' => $profile,
                'given_name' => $user->firstname,
                'family_name' => $user->lastname,
                'email' => $user->email,
                'rquestroles' => $rquestrRoles,
            ]);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}