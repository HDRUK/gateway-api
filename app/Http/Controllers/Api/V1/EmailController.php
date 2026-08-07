<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use App\Models\User;
use App\Services\EmailManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\DispatchEmailRequest;

class EmailController extends Controller
{
    public function dispatchEmail(DispatchEmailRequest $request, EmailManager $emailManager)
    {
        $body = $request->post();

        $user = User::where('id', '=', $body['to'])->first();

        $toArray = [
            'to' => [
                'email' => $user['email'],
                'name' => $user['name'],
            ],
        ];

        $sent = $emailManager->send($body['identifier'], $toArray, $body['replacements']);

        if ($sent) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }
}
