<?php

namespace App\Http\Controllers\SSO;

use CloudLogger;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
// use Laravel\Passport\Bridge\User;
use App\Models\CohortRequest;
use Laravel\Passport\Passport;
use App\Http\Controllers\Controller;
use Laravel\Passport\ClientRepository;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Routing\ResponseFactory;
use Laravel\Passport\Contracts\AuthorizationViewResponse;

class CustomAuthorizationController extends Controller
{
    use HandlesOAuthErrors;

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

    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
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
        // $userId = session('cr_uid');

        // mock user id for with we need:
        // - cohort_regests.request_status = 'APPROVED'
        $cohortRequests = CohortRequest::where(['request_status' => 'APPROVED'])->first();
        $userId = $cohortRequests->user_id;
        // end mock user id

        CloudLogger::write('Start authorization for userId :: ' . $userId);

        $authRequest = $this->withErrorHandling(function () use ($psrRequest) {
            return $this->server->validateAuthorizationRequest($psrRequest);
        });

        $scopes = $this->parseScopes($authRequest);
        $user = User::find($userId);
        $client = $clients->find($authRequest->getClient()->getIdentifier());

        $request->session()->put('authToken', $authToken = Str::random());
        $request->session()->put('authRequest', $authRequest);

        \Log::info(json_encode([
            'client' => $client,
            'user' => $user,
            'scopes' => $scopes,
            'request' => $request,
            'authToken' => $authToken,
        ]));

        return $this->server->completeAuthorizationRequest(
            $authRequest, new Psr7Response
        );
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
