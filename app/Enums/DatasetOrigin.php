<?php

namespace App\Enums;

enum DatasetOrigin: string
{
    case MANUAL = 'MANUAL';
    case API = 'API';
    case GMI = 'GMI';
    case COLLAB = 'COLLAB';
}
