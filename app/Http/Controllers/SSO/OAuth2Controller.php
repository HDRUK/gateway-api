<?php

namespace App\Http\Controllers\SSO;

use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesOAuthErrors;
use App\Models\OauthUser;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
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
    ) {
        $userId = session('cr_uid') ?? config('passport.cr_uid_debug');

        if (! $userId) {
            abort(401, 'User not authenticated!');
        }

        OAuthUser::updateOrCreate([
            'user_id' => $userId,
            'nonce' => '123456789',
        ]);

        return $this->withErrorHandling(function () use ($psrRequest, $userId) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            $user = new User($userId);

            $authRequest->setUser($user);
            $authRequest->setAuthorizationApproved(true);

            return $this->server->completeAuthorizationRequest($authRequest, new Psr7Response());
        });
    }
}
