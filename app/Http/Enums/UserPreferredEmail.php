<?php

namespace App\Http\Enums;

enum UserPreferredEmail: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
}
