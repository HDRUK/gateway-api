<?php

namespace App\Http\Controllers\SSO;

use CloudLogger;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge\User;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use App\Http\Controllers\SSO\HandlesOAuthErrors;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

class CustomAuthorizationController
{
    use HandlesOAuthErrors;
    
    protected $server;

    public function __construct(
        AuthorizationServer $server,
    ) {
        $this->server = $server;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     * @param  \Illuminate\Http\Request  $request
     */
    public function customAuthorize(
        ServerRequestInterface $psrRequest, 
        Request $request,
    )
    {
        $userId = session('cr_uid');

        CloudLogger::write('Start authorization for userId :: ' . $userId);

        return $this->withErrorHandling(function () use ($psrRequest, $request, $userId) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            return $this->approveRequest($authRequest, $userId);
        });
    }

    /**
     * Transform the authorization requests's scopes into Scope instances.
     *
     * @param  AuthRequest  $request
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
            $authRequest, new Psr7Response
        );
    }
}