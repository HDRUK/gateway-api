<?php

namespace App\Enums;

enum FederationJobRunStatus: int
{
    case FAILED = 0;
    case SUCCEEDED = 1;
}
