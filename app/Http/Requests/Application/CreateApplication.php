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
            'app_id' => [
                'required',
                'string',
                'unique:applications,app_id',
            ],
            'client_id' => [
                'required',
                'string',
                'unique:applications,client_id',
            ],
            'image_link' => [
                'required',
                'string',
                'url',
            ],
            'description' => [
                'required',
                'string',
            ],
            'team_id' => [
                'required',
                'exists:teams,id',
            ],
            'user_id' => [
                'required',
                'exists:users,id',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'tags' => [
                'present',
                'array',
            ],
            'tags.*'  => [
                'required',
                'integer', 
                'distinct',
                'exists:tags,id',
            ],
            'permissions' => [
                'present',
                'array',
            ],
            'permissions.*'  => [
                'required',
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ];
    }
}
