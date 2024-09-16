<?php

namespace App\Http\Controllers\SSO;

use CloudLogger;
use App\Models\OauthUser;
use Illuminate\Support\Str;
// use Illuminate\Http\Request;
use Illuminate\Http\Request as LaravelRequest;
use App\Models\CohortRequest;
use Laravel\Passport\Passport;
use App\Models\User as UserModel;
use Laravel\Passport\Bridge\User;
use App\Http\Controllers\Controller;
use Laravel\Passport\ClientRepository;
use App\Http\Traits\HandlesOAuthErrors;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Routing\ResponseFactory;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Controllers\RetrievesAuthRequestFromSession;

use Illuminate\Support\Facades\Auth;

class CustomAuthorizationController extends Controller
{
    use HandlesOAuthErrors, RetrievesAuthRequestFromSession;

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
        ClientRepository $clients,
    ) {
        $request = new LaravelRequest(
            $psrRequest->getQueryParams(),
            $psrRequest->getParsedBody(),
            [], // Attributes
            $psrRequest->getCookieParams(),
            $psrRequest->getUploadedFiles(),
            $psrRequest->getServerParams(),
            $psrRequest->getBody()->__toString()
        );

        // Set method and headers
        $request->setMethod($psrRequest->getMethod());
        foreach ($psrRequest->getHeaders() as $name => $values) {
            $request->headers->set($name, implode(', ', $values));
        }

        // Ensure Content-Type is set
        if (!$request->headers->has('Content-Type')) {
            $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
        }
        
        // $userId = session('cr_uid');

        // mock user id for with we need:
        // - cohort_regests.request_status = 'APPROVED'
        // $cohortRequests = CohortRequest::where(['request_status' => 'APPROVED'])->first();
        $cohortRequests = CohortRequest::where(['user_id' => 3946])->first();
        $userId = $cohortRequests->user_id;

        // this is only temporary - it needs to be there when the flow starts in the backend
        OauthUser::where('user_id', $userId)->delete();
        OauthUser::create([
            'user_id' => $userId,
            'nonce' => $request->query('nonce'),
        ]);

        // Debugging: Log request parameters
        \Log::info('Request parameters : ' . json_encode($request->all()));

        return $this->withErrorHandling(function () use ($psrRequest, $request, $userId) {
            // Additional debugging
            \Log::info('Auth request : ' . json_encode([
                'grant_type' => $request->input('grant_type'),
                'client_id' => $request->input('client_id'),
            ]));

            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            \Log::info('authRequest :: ' . json_encode($authRequest));

            return $this->approveRequest($authRequest, $userId, $request);
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
    private function approveRequest($authRequest, int $userId, LaravelRequest $request)
    {
        \Log::info('Approving authorization request', [
            'user_id' => $userId,
            'client_id' => $authRequest->getClient()->getIdentifier(),
            'grant_type' => $request->input('grant_type'),
        ]);

        $authRequest->setUser(new User($userId));

        $authRequest->setAuthorizationApproved(true);

        return $this->server->completeAuthorizationRequest(
            $authRequest,
            new Psr7Response()
        );
    }
}
