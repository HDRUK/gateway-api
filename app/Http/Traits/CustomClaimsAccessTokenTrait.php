<?php

namespace App\Http\Traits;

use App\Models\User;
use Lcobucci\JWT\Token;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasPermission;
use App\Models\Permission;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;

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

        $ga4ghVisaV1 = [
            'type' => 'AffiliationAndRole',
            'asserted' => $user->created_at,
            'value' => 'no.organization',
            'source' => env('GATEWAY_URL'), // temp
        ];

        return $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new \DateTimeImmutable())
            ->issuedBy(env('GATEWAY_URL'))
            ->canOnlyBeUsedAfter(new \DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string)$this->getUserIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->withClaim('email', $user->email)
            ->withClaim('preferred_username', $user->name)
            ->withClaim('fullname', $user->lastname . ' ' . $user->firstname)
            ->withClaim('firstname', $user->firstname)
            ->withClaim('lastname', $user->lastname)
            ->withClaim('rquestroles', $rquestroles)
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
