<?php

namespace App\Http\Requests\SavedSearch;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;
use App\Http\Enums\SortOrderSavedSearch;

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
            'enabled' => [
                'boolean',
            ],
            'sort_order' => [
                'string',
                Rule::in(['score','title:asc','title:desc','updated_at:desc','updated_at:asc']),
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