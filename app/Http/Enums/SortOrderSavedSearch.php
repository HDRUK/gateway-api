<?php

namespace App\Http\Enums;

enum SortOrderSavedSearch: string
{
    case MOST_RELEVANT = 'most_relevant';
    case SORT_TITLE_ASC = 'title:asc';
    case SORT_TITLE_DESC = 'title:desc';
    case MOST_RECENTLY_UPDATED = 'updated_at:desc';
    case LEAST_RECENTLY_UPDATED = 'updated_at:asc';
}
