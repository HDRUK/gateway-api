<?php

namespace App\Http\Enums;

enum TeamMemberOf: string
{
    case ALLIANCE = 'ALLIANCE';
    case HUB = 'HUB';
    case OTHER = 'OTHER';
}
