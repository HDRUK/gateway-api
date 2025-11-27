<?php

namespace App\Http\Controllers\SSO;

use App\Models\OauthUser;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\User;
use App\Http\Controllers\Controller;
use Laravel\Passport\ClientRepository;
use App\Http\Traits\HandlesOAuthErrors;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Routing\ResponseFactory;
use Laravel\Passport\Http\Controllers\RetrievesAuthRequestFromSession;

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

        error_log("\033[31mUSER ID --$userId-- \033[0m");

        if (!$userId) {
            abort(401, 'User not authenticated');
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
