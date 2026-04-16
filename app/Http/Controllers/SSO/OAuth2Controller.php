<?php

namespace App\Http\Controllers\SSO;

use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesOAuthErrors;
use App\Models\OauthUser;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\RetrievesAuthRequestFromSession;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;

class OAuth2Controller extends Controller
{
    use HandlesOAuthErrors;
    use RetrievesAuthRequestFromSession;

    protected AuthorizationServer $server;
    protected ResponseFactory $response;

    public function __construct(AuthorizationServer $server, ResponseFactory $response)
    {
        $this->server = $server;
        $this->response = $response;
    }

    public function customAuthorize(
        ServerRequestInterface $psrRequest,
        Request $request,
        ClientRepository $clients,
    ): Response {
        $state = $request->query('state');

        try {
            $userId = $state ? decrypt($state) : null;
        } catch (\Exception $e) {
            return $this->response->make('User not authenticated', 401);
        }

        if (!$userId) {
            return $this->response->make('User not authenticated', 401);
        }

        $nonce = $request->query('nonce');

        if (!is_string($nonce) || trim($nonce) === '') {
            abort(400, 'Missing nonce');
        }

        OauthUser::updateOrCreate(
            ['user_id' => $userId],
            ['nonce' => $nonce]
        );

        return $this->withErrorHandling(function () use ($psrRequest, $userId) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            $user = new User($userId);

            $authRequest->setUser($user);
            $authRequest->setAuthorizationApproved(true);

            return $this->server->completeAuthorizationRequest($authRequest, new Psr7Response());
        });
    }
}
