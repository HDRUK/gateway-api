<?php

namespace App\Http\Controllers\SSO;

use Illuminate\Support\Facades\Cookie;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CustomLogoutController extends Controller
{
    /**
     * constructor
     */
    public function __constructor()
    {
        //
    }

    public function logOut(Request $request)
    {
        try {
            $user = Auth::user();
            \Log::info('CustomLogoutController userInfo :: ' . json_encode($user));

            $user = $request->user();
            \Log::info('CustomLogoutController userInfo request :: ' . json_encode($request->user()));

            // $accessToken = $user->token();
            // \Log::info('CustomLogoutController accessToken request :: ' . json_encode($accessToken));
        
            // \DB::table('oauth_refresh_tokens')->where('access_token_id', $accessToken->id)->delete();

            // $accessToken->delete();
        
            $cookies = [
                Cookie::make('token', 'test'),
            ];
        
            $redirectUrl = env('GATEWAY_URL');
            // return redirect()->away($redirectUrl)->withCookies($cookies);
            return redirect()->away($redirectUrl);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}