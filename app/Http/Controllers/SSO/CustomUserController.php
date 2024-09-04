<?php

namespace App\Http\Controllers\SSO;


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
        $user = Auth::user();

        \Log::info('CustomUserController - $user :: ' . json_encode($user));

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'preferred_username' => $user->name,
            'given_name' => $user->firstname,
            'family_name' => $user->family_name,
            'email' => $user->email,
            'rquestroles' => $user->rquestroles, // no idea if is ok
        ]);
    }
}