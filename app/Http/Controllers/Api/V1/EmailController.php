<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\DispatchEmailRequest;

class EmailController extends Controller
{
    public function dispatchEmail(DispatchEmailRequest $request)
    {
        $body = $request->post();

        $template = EmailTemplate::where('identifier', '=', $body['identifier'])->first();
        $user = User::where('id', '=', $body['to'])->first();

        $toArray = [
            'to' => [
                'email' => $user['email'],
                'name' => $user['name'],
            ],
        ];

        if ($template) {
            SendEmailJob::dispatch($toArray, $template, $body['replacements']);
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }
}
