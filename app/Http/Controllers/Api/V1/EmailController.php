<?php

namespace App\Http\Controllers\Api\V1;

use Config;

use App\Mail\Email;

use App\Jobs\SendEmailJob;

use App\Models\User;
use App\Models\EmailTemplate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function dispatchEmail(Request $request)
    {
        $request->validate([
            'to' => 'required|int',
            'identifier' => 'required|string',
            'replacements' => 'required',
        ]);

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
            dd('here');
            return response()->json([
                'message' => 'OK',
            ], 200);
        }

        return response()->json([
            'message' => 'NOT OK',
        ], 404);
    }
}
