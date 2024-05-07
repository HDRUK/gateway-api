<?php

namespace App\Http\Requests\SavedSearch;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;
use App\Http\Enums\SortOrderSavedSearch;

class UpdateSavedSearch extends BaseFormRequest
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
                Rule::in([
                    SortOrderSavedSearch::ASCENDENT,
                    SortOrderSavedSearch::DESCENDENT,
                ]),
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