<?php

namespace App\Enums;

enum ReassignableEntityType: string
{
    case DATASET = 'dataset';
    case TOOL = 'tool';
    case APPLICATION = 'application';
    case REVIEW = 'review';
    case COHORT_REQUEST = 'cohort_request';
    case ENQUIRY_THREAD = 'enquiry_thread';
    case COLLECTION = 'collection';
}
