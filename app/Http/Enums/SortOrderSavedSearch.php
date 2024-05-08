<?php

namespace App\Http\Enums;

enum SortOrderSavedSearch: string
{
    case MOST_RELEVANT = 'score';
    case SORT_TITLE_ASC = 'title_asc';
    case SORT_TITLE_DESC = 'title_desc';
    case MOST_RECENTLY_UPDATED = 'updated_at_desc';
    case LEAST_RECENTLY_UPDATED = 'updated_at_asc';
}
