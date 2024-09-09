<?php

namespace App\Http\Controllers\SSO;

use Exception;
use Illuminate\Http\Request;
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

            \Log::info('CustomUserController userInfo :: ' . json_encode($user));
            \Log::info('CustomUserController userInfo request :: ' . json_encode($request->user()));

            $profile = [
                $user->firstname,
                $user->lastname,
            ];

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'preferred_username' => $user->name,
                'firstName' => $user->firstname,
                'lastName' => $user->lastname,
                'profile' => $profile,
                'given_name' => $user->firstname,
                'family_name' => $user->lastname,
                'email' => $user->email,
                'rquestroles' => ['GENERAL_ACCESS', 'SYSTEM_ADMIN'],
            ]);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}