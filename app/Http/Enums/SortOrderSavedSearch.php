<?php

namespace App\Http\Enums;

enum SortOrderSavedSearch: string
{
    case MOST_RELEVANT = 'most_relevant';
    case SORT_TITLE_ASC = 'title_asc';
    case SORT_TITLE_DESC = 'title_desc';
    case MOST_RECENTLY_UPDATED = 'updated_desc';
    case LEAST_RECENTLY_UPDATED = 'updated_asc';
}
