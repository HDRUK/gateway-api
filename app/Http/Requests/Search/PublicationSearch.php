<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class PublicationSearch extends BaseFormRequest
{
    private function validateQuery($query)
    {
        if (filter_var($query, FILTER_VALIDATE_URL)) {
            $parse = parse_url($query);
            if ($parse['host'] === "doi.org") {
                return $query;
            }
        }

        return preg_replace('/[^a-zA-Z0-9_-]/', '', $query);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'query' => $this->validateQuery($this->input('query')),
            'source' => $this->source ?? 'GAT'
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
                'regex:/^(projectTitle|created_at|year_of_publication|updated_at|name|score|date|title):(asc|desc)$/i',
                'nullable'
            ],
        ];
    }
}
