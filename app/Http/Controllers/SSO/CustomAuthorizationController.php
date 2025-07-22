<?php

namespace App\Http\Controllers\SSO;

use CloudLogger;
use App\Models\OauthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge\User;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Laravel\Passport\ClientRepository;
use App\Http\Traits\HandlesOAuthErrors;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Routing\ResponseFactory;
use Laravel\Passport\Http\Controllers\RetrievesAuthRequestFromSession;

class CustomAuthorizationController extends Controller
{
    use HandlesOAuthErrors;
    use RetrievesAuthRequestFromSession;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * The response factory implementation.
     *
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $response;

    public function __construct(AuthorizationServer $server, ResponseFactory $response)
    {
        $this->server = $server;
        $this->response = $response;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     */
    public function customAuthorize(
        ServerRequestInterface $psrRequest,
        Request $request,
        ClientRepository $clients,
    ) {
        CloudLogger::write('Session data for customAuthorize :: ' . json_encode([
            'session' => session()->all(),
            'request' => $request->all(),
        ]));

        // user_id from CohortRequestController@checkAccess
        $userId = session('cr_uid');

        // if (!$userId) {
        //     CloudLogger::write('No user_id/cr_uid found in session :: ' . json_encode([
        //         'session' => session()->all(),
        //     ]));
        //     return redirect()->away(env('GATEWAY_URL', 'http://localhost'));
        // }

        if (!$userId) {
            CloudLogger::write('No user_id/cr_uid found in session :: ' . json_encode([
                'session' => session()->all(),
            ]));

            $getFromDB = OauthUser::where('updated_at', '>=', Carbon::now()->subSeconds(3))->first();
            if ($getFromDB) {
                $userId = $getFromDB->user_id;
                CloudLogger::write('Found user_id from OauthUser :: ' . $userId);
            } else {
                return redirect()->away(env('GATEWAY_URL', 'http://localhost'));
            }
        }

        // save nonce and user_id for id_token
        $checkifExists = OauthUser::where('user_id', $userId)->first();
        if ($checkifExists) {
            CloudLogger::write('OauthUser already exists for user_id :: ' . $userId . ', n_nce :: ' . $checkifExists->nonce);
            OauthUser::where('user_id', $userId)->update([
                'nonce' => $request->query('nonce'),
            ]);
        } else {
            CloudLogger::write('Creating new OauthUser for user_id :: ' . $userId . ', n_nce :: ' . $request->query('nonce'));
            OauthUser::create([
                'user_id' => $userId,
                'nonce' => $request->query('nonce'),
            ]);
        }

        return $this->withErrorHandling(function () use ($psrRequest, $userId) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            return $this->approveRequest($authRequest, $userId);
        });
    }

    /**
     * Transform the authorization requests's scopes into Scope instances.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @return array
     */
    protected function parseScopes($authRequest)
    {
        return Passport::scopesFor(
            collect($authRequest->getScopes())->map(function ($scope) {
                return $scope->getIdentifier();
            })->unique()->all()
        );
    }

    /**
     * Approve the authorization request.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @param  Integer  $userId
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function approveRequest($authRequest, int $userId)
    {
        $authRequest->setUser(new User($userId));

        $authRequest->setAuthorizationApproved(true);

        return $this->server->completeAuthorizationRequest(
            $authRequest,
            new Psr7Response()
        );
    }
}
