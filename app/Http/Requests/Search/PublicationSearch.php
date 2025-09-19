<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class PublicationSearch extends BaseFormRequest
{
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
                'sometimes',
                'array',
            ],
            'query.*' => [
                'nullable',
                'alpha_num:ascii',
                'max:255'
            ],
            'source' => [
                'nullable',
                'string',
                'in:GAT,FED',
            ],
        ];
    }

    /**
     * Add Query parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['source' => $this->query('source')]);
    }
}
