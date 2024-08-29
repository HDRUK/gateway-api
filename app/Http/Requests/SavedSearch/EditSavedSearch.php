<?php

namespace App\Http\Requests\SavedSearch;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class EditSavedSearch extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'int',
                'required',
                'exists:saved_searches,id',
            ],
            'name' => [
                'string',
            ],
            'search_term' => [
                'nullable',
                'string',
            ],
            'enabled' => [
                'boolean',
            ],
            'sort_order' => [
                'string',
                Rule::in(['score:desc', 'name:asc', 'name:desc', 'created_at:asc', 'created_at:desc']),
            ],
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
