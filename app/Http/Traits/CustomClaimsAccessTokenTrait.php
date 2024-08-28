<?php

namespace App\Http\Traits;

use App\Models\User;
use Lcobucci\JWT\Token;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasPermission;
use App\Models\Permission;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use Log;

trait CustomClaimsAccessTokenTrait
{
    use AccessTokenTrait;

    /**
     * Generate a JWT from the access token
     *
     * @return Token
     */
    private function convertToJWT()
    {
        // https://github.com/HDRUK/gateway-api/blob/2f0f2df3d94a75b8a1a4920a64cd0c6a2267c2d3/src/resources/utilities/ga4gh.utils.js#L10

        $this->initJwtConfiguration();

        $rquestroles = $this->getRquestroles($this->getUserIdentifier());

        $user = User::where([
            'id' => $this->getUserIdentifier(),
        ])->first();

        $allowedOrigins = [
            "*",
            env('APP_URL') . "/*",
        ];
        // $realmAccess = [
        //     "roles" => $rquestroles,
        // ];
        $realmAccess = [
            "roles" => [
                "default-roles-rquest-206",
                "offline_access",
                "uma_authorization"
            ],
        ];
        $resourceAccess = [
            "account" => [
                "roles" => [
                    "manage-account",
                    "manage-account-links",
                    "view-profile"
                ]
            ]
        ];
        // $sessionState = (string)session()->getId();
        $sessionState = "ae038c99-8244-4d8e-a85d-e8648fb9dbcd";
        $identifiedBy = $this->getIdentifier();

        return $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($identifiedBy)
            ->issuedAt(new \DateTimeImmutable())
            // ->issuedBy(env('GATEWAY_URL'))
            ->issuedBy(env('APP_URL'))
            ->canOnlyBeUsedAfter(new \DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string)$this->getUserIdentifier())
            ->withClaim('typ', "Bearer")
            // ->withClaim('auth_time', 0)
            ->withClaim('session_state', $sessionState)
            ->withClaim('session_state', $sessionState)
            ->withClaim('sid', $sessionState)
            ->withClaim('allowed-origins', $allowedOrigins)
            ->withClaim('email_verified', true)
            ->withClaim('email', $user->email)
            ->withClaim('preferred_username', $user->name)
            ->withClaim('name', $user->lastname . ' ' . $user->firstname)
            ->withClaim('given_name', $user->firstname)
            ->withClaim('family_name', $user->lastname)
            // ->withClaim('rquestroles', $rquestroles)
            ->withClaim('realm_access', $realmAccess)
            ->withClaim('resource_access', $resourceAccess)
            ->withClaim('scope', "openid profile email")
            ->withHeader('kid', env('JWT_KID', 'jwtkidnotfound'))
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }

    /**
     * Generate a string representation from the access token
     */
    public function __toString()
    {
        return $this->convertToJWT()->toString();
    }

    public function getRquestroles($id)
    {
        $cohortRequest = CohortRequest::where([
            'user_id' => $id,
            'request_status' => 'APPROVED',
        ])->first();

        if (!$cohortRequest) {
            return [];
        }

        $cohortRequestRoleIds = CohortRequestHasPermission::where([
            'cohort_request_id' => $cohortRequest->id
        ])->pluck('permission_id')->toArray();

        $cohortRequestRoles = Permission::whereIn('id', $cohortRequestRoleIds)->pluck('name')->toArray();

        return $cohortRequestRoles;
    }
}
