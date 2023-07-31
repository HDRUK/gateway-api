<?php

namespace App\Http\Enums;

enum DatasetState: string
{
    case ACTIVE = 'active';
    case ARCHIVE = 'archive';
    case DRAFT = 'draft';
    case INREVIEW = 'inReview';
    case REJECTED = 'rejected';
    case REVIEW = 'review';
}
