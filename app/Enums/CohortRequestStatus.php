<?php

namespace App\Enums;

enum CohortRequestStatus: string
{
    case APPROVED = 'APPROVED';
    case PENDING = 'PENDING';
    case REJECTED = 'REJECTED';
    case BANNED = 'BANNED';
    case SUSPENDED = 'SUSPENDED';
    case EXPIRED = 'EXPIRED';
}
