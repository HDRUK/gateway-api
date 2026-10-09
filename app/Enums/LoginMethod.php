<?php

namespace App\Enums;

enum LoginMethod: string
{
    case GOOGLE = 'google';
    case LINKEDIN = 'linkedin';
    case AZURE = 'azure';
    case OPENATHENS = 'openathens';
    case REGISTRY = 'registry';
    case PASSWORD = 'password';
    case CRUK = 'cruk';
    case OAUTH = 'oauth';
}
