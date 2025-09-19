<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class Search extends BaseFormRequest
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
                'alpha_dash',
                'nullable',
                'max:255'
            ],
            'sort' => [
                'regex:/^(projectTitle|updated_at|name|score|date|title):(asc|desc)$/i'
            ],
            'download' => 'boolean',
        ];
    }
}
