<?php

namespace App\Http\Controllers\SSO;

use Exception;
use Illuminate\Http\Request;
use App\Models\CohortRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

            $cohortDiscoveryRoles = CohortRequest::rolesForUser($userId);

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
                'rquestroles' => $cohortDiscoveryRoles,
                'cohort_discovery_roles' => $cohortDiscoveryRoles,
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
