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

    public function rquestLogout(Request $request)
    {
        try {
            // no info from rquest in logout
            // $user = Auth::user();
            // Auth::guard()->logout();
            $request->session()->flush();
            \Log::info('CustomLogoutController request :: ' . json_encode($request));
        
            $redirectUrl = env('GATEWAY_URL');
            return redirect()->away($redirectUrl);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }
}
