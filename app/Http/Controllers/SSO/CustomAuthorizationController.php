<?php

namespace App\Http\Controllers\SSO;

use CloudLogger;
use Illuminate\Http\Request;
use App\Models\CohortRequest;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge\User;
use App\Http\Controllers\Controller;
use App\Services\CloudLoggerService;
use Laravel\Passport\ClientRepository;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use App\Http\Controllers\SSO\HandlesOAuthErrors;
use Illuminate\Contracts\Routing\ResponseFactory;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

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

    protected $cloudLogger;

    public function __construct(AuthorizationServer $server, ResponseFactory $response, CloudLoggerService $cloudLogger)
    {
        $this->server = $server;
        $this->response = $response;
        $this->cloudLogger = $cloudLogger;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     */
    public function customAuthorize(
        ServerRequestInterface $psrRequest, 
    )
    {
        // $userId = session('cr_uid');

        // mock user id for with we need:
        // - cohort_regests.request_status = 'APPROVED'
        $cohortRequests = CohortRequest::where(['request_status' => 'APPROVED'])->first();
        $userId = $cohortRequests->user_id;
        // end mock user id

        $this->cloudLogger->write('Start authorization for userId :: ' . $userId);

        return $this->withErrorHandling(function () use ($psrRequest, $userId) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            return $this->approveRequest($authRequest, $userId);
        });
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