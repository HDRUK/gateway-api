<?php

namespace App\Http\Controllers\SSO;

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

        Log::info('CustomLogoutController request :: ' . json_encode($request->all()));
        Log::info('CustomLogoutController psrRequest :: ' . json_encode($psrRequest->getParsedBody()));

        $userId = null;
        if (session()->has('cr_uid')) {
            $userId = session('cr_uid');
            session()->forget('cr_uid');
        }

        // user_id from CohortRequestController@checkAccess
        // $userId = $request->session('cr_uid');
        
        
        Log::info('Session data customAuthorize :: ' . json_encode(session()->all()));

        if (!$userId) {
            Log::error('User Id not found in session');

            // $findUser = OauthUser::where('created_at', '>=', Carbon::now()->subSeconds(63))
            $findUser = OauthUser::where('created_at', '>=', Carbon::now()->subHours(4))
                ->where('nonce', 'new_nonce')
                ->latest('created_at')
                ->first();
            Log::info('findUser :: ' . json_encode($findUser));

            if (is_null($findUser)) {
                Log::error('User Id not found in session or OauthUser table');
                Log::info('Session data :: ' . json_encode(session()->all()));
                return redirect()->away(env('GATEWAY_URL', 'http://localhost'));
            } else {
                $userId = $findUser->user_id;
            }
        }

        // save nonce and user_id for id_token
        $findUserNewNonce = OauthUser::where([
            'user_id' => $userId,
            'nonce' => 'new_nonce'
        ])->first();
        Log::info('findUserNewNonce :: ' . json_encode($findUserNewNonce));

        if (is_null($findUserNewNonce)) {
            OauthUser::create([
                'user_id' => $userId,
                'nonce' => $request->query('nonce'),
            ]);
        } else {
            OauthUser::where([
                'id' => $findUserNewNonce->id,
            ])->update(['nonce' => $request->query('nonce')]);
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
