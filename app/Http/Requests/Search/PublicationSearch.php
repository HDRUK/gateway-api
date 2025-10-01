<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class PublicationSearch extends BaseFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'query' => preg_replace('/[^a-zA-Z0-9_-]/', '', $this->input('query')),
        ]);
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
                'regex:/^(projectTitle|updated_at|name|score|date|title):(asc|desc)$/i'
            ],
        ];
    }
}
