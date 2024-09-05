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
    
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'preferred_username' => $user->name,
                'given_name' => $user->firstname,
                'family_name' => $user->family_name,
                'email' => $user->email,
                'rquestroles' => $user->rquestroles, // no idea if is ok
            ]);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}