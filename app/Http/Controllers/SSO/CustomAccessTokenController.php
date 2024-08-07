<?php

namespace App\Http\Controllers\SSO;

use CloudLogger;
use App\Models\User;
use Psr\Http\Message\ServerRequestInterface;
use Laravel\Passport\Exceptions\OAuthServerException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Passport\Http\Controllers\AccessTokenController as AuthController;

class CustomAccessTokenController extends AuthController
{
    public function customIssueToken(ServerRequestInterface $request)
    {
        // try {

            // $response = parent::issueToken($request);

            // $datatest = json_decode($response->getBody(), true);
            // CloudLogger::write('datatest :: ' . $datatest);

            // Extract request data
            $data = json_decode($request->getBody()->getContents(), true);
            CloudLogger::write('request :: ' . json_encode($request));
            CloudLogger::write('request getbody :: ' . json_encode($request->getBody()));
            CloudLogger::write('data :: ' . json_encode($data));
            \Log::info(json_encode($data));

            // Custom validation
            // if (!isset($data['username']) || !isset($data['password'])) {
            //     throw new OAuthServerException('Missing credentials', 400, 'invalid_request');
            // }

            // Additional security check (example: check if user is banned)
            // $user = User::where('email', $data['username'])->first();
            // if ($user && $user->is_banned) {
            //     throw new OAuthServerException('User is banned', 403, 'access_denied');
            // }

            // // Log the token request
            // // \Log::info('Token requested for user: ' . $data['username']);
            // CloudLogger::write('Token requested for user: ' . $data['username']);

            // Call the parent method to issue the token
            $tokenResponse = parent::issueToken($request);
            CloudLogger::write('tokenResponse :: ' . json_encode($tokenResponse));

            // Decode the response
            $content = json_decode($tokenResponse->getContent(), true);

            // Add custom data to the response
            // $content['custom_data'] = [
            //     'user_id' => $user->id,
            //     'roles' => $user->roles->pluck('name'),
            //     'last_login' => now()->toDateTimeString(),
            // ];
            $content['custom_data'] = [
                'user_id' => 1,
                'roles' => ['GENERAL_ACCESS'],
                'last_login' => now()->toDateTimeString(),
            ];

            // Update user's last login time
            // $user->update(['last_login_at' => now()]);

            // Return custom response
            return response()->json($content, $tokenResponse->status());

        // } catch (OAuthServerException $e) {
        //     // Log the error
        //     \Log::error('Token issuance failed: ' . $e->getMessage());

        //     // Return error response
        //     return response()->json([
        //         'message' => $e->getMessage(),
        //     ], $e->getCode());
        // }
    }
}