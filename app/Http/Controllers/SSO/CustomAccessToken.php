<?php

namespace App\Http\Controllers\SSO;

use Laravel\Passport\Bridge\AccessToken;
use App\Http\Traits\CustomClaimsAccessTokenTrait;

class CustomAccessToken extends AccessToken
{
    use CustomClaimsAccessTokenTrait;
}