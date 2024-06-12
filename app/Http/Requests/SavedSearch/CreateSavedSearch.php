<?php

namespace App\Http\Requests\SavedSearch;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;
use App\Http\Enums\SortOrderSavedSearch;

class CreateSavedSearch extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
            ],
            'search_term' => [
                'required',
            ],
            'search_endpoint' => [
                'required',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'sort_order' => [
                'required',
                'string',
                Rule::in(['score','title:asc','title:desc','updated_at:desc','updated_at:asc']),
            ],
        ];
    }
}
