<?php

namespace App\Enums;

enum CohortRequestEligibility
{
    case RENEW;
    case ALREADY_RENEWING;
    case REAPPLY;
    case BLOCKED;
}
