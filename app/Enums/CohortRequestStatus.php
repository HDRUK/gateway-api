<?php

namespace App\Enums;

enum CohortRequestStatus: string
{
    case APPROVED = 'APPROVED';
    case RENEWING = 'RENEWING';
    case PENDING = 'PENDING';
    case REJECTED = 'REJECTED';
    case BANNED = 'BANNED';
    case SUSPENDED = 'SUSPENDED';
    case EXPIRED = 'EXPIRED';
}
