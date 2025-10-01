<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class Search extends BaseFormRequest
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
                'alpha_dash',
                'nullable',
                'max:255'
            ],
            'sort' => [
                'regex:/^(projectTitle|updated_at|name|score|date|title):(asc|desc)$/i'
            ],
            'page' => 'integer',
            'view_type' => ['nullable', 'in:full,mini'],
            'per_page' => 'integer',
            'download' => 'boolean',
        ];
    }
}
