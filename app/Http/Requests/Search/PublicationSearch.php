<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class PublicationSearch extends BaseFormRequest
{
    /**
     * Add Query parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['source' => $this->query('source')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'query' => [
                'nullable',
                'max:255',
            ],
            'source' => [
                'nullable',
                'string',
                'in:GAT,FED',
            ],
            'page' => 'integer',
            'per_page' => 'integer',
            'sort' => [
                'regex:/^(projectTitle|created_at|year_of_publication|updated_at|name|score|date|title):(asc|desc)$/i',
                'nullable'
            ],
        ];
    }
}
