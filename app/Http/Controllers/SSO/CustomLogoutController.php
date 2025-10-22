<?php

namespace App\Http\Controllers\SSO;

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

            $redirectUrl = config('gateway.gateway_url');
            return redirect()->away($redirectUrl);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
