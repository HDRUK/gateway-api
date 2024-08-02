<?php

namespace App\Http\Requests\SavedSearch;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

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
                'nullable',
                'string',
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
                Rule::in(['score:desc', 'name:asc', 'name:desc', 'created_at:asc', 'created_at:desc']),
            ],
        ];
    }
}
