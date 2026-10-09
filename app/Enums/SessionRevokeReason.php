<?php

namespace App\Enums;

enum SessionRevokeReason: string
{
    case LOGOUT = 'logout';
    case ADMIN = 'admin';
    case REVOKE_ALL = 'revoke_all';
}
