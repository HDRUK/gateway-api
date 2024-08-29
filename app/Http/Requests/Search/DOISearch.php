<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class DOISearch extends BaseFormRequest
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
                'required',
                'string',
                'max:255'
            ]
        ];
    }
}
