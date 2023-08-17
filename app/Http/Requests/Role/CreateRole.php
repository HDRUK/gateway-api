<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseFormRequest;

class CreateRole extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'unique:roles,name',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'permissions' => [
                'required',
                'array',
            ],
        ];
    }
}
