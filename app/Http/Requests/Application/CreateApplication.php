<?php

namespace App\Http\Requests\Application;

use App\Http\Requests\BaseFormRequest;

class CreateApplication extends BaseFormRequest
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
            ],
            'image_link' => [
                'string',
                'url',
            ],
            'description' => [
                'required',
                'string',
            ],
            'team_id' => [
                'required',
                'integer',
                'exists:teams,id',
            ],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'tags' => [
                'array',
            ],
            'tags.*'  => [
                'integer', 
                'distinct',
                'exists:tags,id',
            ],
            'permissions' => [
                'array',
            ],
            'permissions.*'  => [
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ];
    }
}
