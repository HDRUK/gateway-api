<?php

namespace App\Http\Requests\TypeCategory;

use App\Http\Requests\BaseFormRequest;

class CreateTypeCategory extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:type_categories,name',
            ],
            'description' => [
                'required',
                'string',
                'max:1000',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
