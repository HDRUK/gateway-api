<?php

namespace App\Http\Enums;

enum ActivityLogUserType: string
{
    case ADMIN = 'admin';
    case CUSTODIAN = 'custodian';
    case APPLICANT = 'applicant';
}
