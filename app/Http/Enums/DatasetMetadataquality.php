<?php

namespace App\Http\Enums;

enum DatasetMetadataquality: string
{
    case BRONZE = 'Bronze';
    case GOLD = 'Gold';
    case NOTRATED = 'Not Rated';
    case PLATINUM = 'Platinum';
    case SILVER = 'Silver';
}
