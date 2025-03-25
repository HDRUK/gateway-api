<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\AdminControlTriggerTermExtractionDirector;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminPanelController extends Controller
{
    public function triggerTermExtractionDirector(Request $request)
    {
        $input = $request->all();

        // Rather poor, but pretty secure method to police these
        // temporary routes
        if (!isset($input['secret_key']) || !$input['secret_key'] === env('APP_AUTH_PRIVATE_SALT')) {
            return response()->json([
                'message' => 'not allowed',
            ], 401);
        }

        AdminControlTriggerTermExtractionDirector::dispatch();
    }
}
