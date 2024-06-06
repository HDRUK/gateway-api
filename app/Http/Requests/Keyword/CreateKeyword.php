<?php

namespace App\Http\Requests\Keyword;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateKeyword extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('keywords')->where(function ($query) {
                    $query->where('name', trim($this->name));
                }),
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
