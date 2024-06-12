<?php

namespace App\Http\Enums;

enum UserContactPreference: string
{
    case USER_NEWS = 'Gateway Newsletter';
    case USER_FEEDBACK = 'Gateway Feedback';
}
