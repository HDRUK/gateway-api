<?php

namespace App\Http\Requests\ProgrammingLanguage;

use App\Http\Requests\BaseFormRequest;

class CreateProgrammingLanguage extends BaseFormRequest
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
                'unique:programming_languages,name',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
