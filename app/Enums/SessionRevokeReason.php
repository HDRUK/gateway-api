<?php

namespace App\Enums;

enum SessionRevokeReason: string
{
    case LOGOUT = 'logout';
    case ADMIN = 'admin';
    case EMERGENCY = 'emergency';
}
