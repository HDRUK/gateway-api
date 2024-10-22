<?php

namespace App\Http\Controllers\SSO;

use Laravel\Passport\Bridge\AccessToken;
use App\Http\Traits\CustomAccessTokenTrait;

class CustomAccessToken extends AccessToken
{
    use CustomAccessTokenTrait;
}
