<?php

namespace App\Http\Controllers\SSO;

use CloudLogger;
use App\Models\OauthUser;
use Illuminate\Support\Str;
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
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

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
        LaravelRequest $request,
        ClientRepository $clients,
    ) {
        // $userId = session('cr_uid');

        // mock user id for with we need:
        // - cohort_regests.request_status = 'APPROVED'
        $cohortRequests = CohortRequest::where(['request_status' => 'APPROVED'])->first();
        $userId = $cohortRequests->user_id;
        // end mock user id

        // this is only temporary - it needs to be there when the flow starts in the backend
        OauthUser::where('user_id', $userId)->delete();
        OauthUser::create([
            'user_id' => $userId,
            'nonce' => $request->query('nonce'),
        ]);

        // Convert PSR-7 request to Symfony request
        $httpFoundationFactory = new HttpFoundationFactory();
        $symfonyRequest = $httpFoundationFactory->createRequest($psrRequest);

        // Convert Symfony request to Laravel request
        $request = LaravelRequest::createFromBase($symfonyRequest);
        
        return $this->withErrorHandling(function () use ($psrRequest, $request, $userId) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            $approveRequest =  $this->approveRequest($authRequest, $userId, $request);
            \Log::info('approveRequest :: ' . json_encode($approveRequest));
            return $approveRequest;
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
