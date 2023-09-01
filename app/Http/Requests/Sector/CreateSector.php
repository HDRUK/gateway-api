<?php

namespace App\Http\Requests\Sector;

use App\Http\Requests\BaseFormRequest;

class CreateSector extends BaseFormRequest
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
                'unique:sectors,name',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
