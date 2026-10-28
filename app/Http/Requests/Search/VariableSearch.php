<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;
use App\Services\Search\VariableSearch as VariableSearchService;

class VariableSearch extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // The comparison view may list datasets before any term is added.
            'terms' => ['required_without:dataset_ids', 'array', 'max:'.(int) config('typesense.variables.max_terms', 6)],
            'terms.*' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'distinct:ignore_case',
                'regex:/^[^<>${}`\x00]*$/',
            ],
            'match' => ['nullable', 'in:'.VariableSearchService::MATCH_ALL.','.VariableSearchService::MATCH_ANY],
            'dataset_ids' => ['nullable', 'array', 'max:'.(int) config('typesense.variables.max_datasets', 50)],
            'dataset_ids.*' => ['integer', 'min:1'],
        ];
    }
}
