<?php

namespace App\Http\Requests\Feature;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateFeature extends BaseFormRequest
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
                Rule::unique('features')->where(function ($query) {
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
