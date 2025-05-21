<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class CreateUser extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'firstname' => [
                'required',
                'string',
            ],
            'lastname' => [
                'required',
                'string',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'unique:users,email',
            ],
            'secondary_email' => [
                'nullable',
                'string',
                'email',
            ],
            'preferred_email' => [
                'string',
                'in:primary,secondary',
            ],
            'password' => [
                'nullable',
                'string',
            ],
            'sector_id' => [
                'required',
                'integer',
                'exists:sectors,id',
            ],
            'organisation' => [
                'nullable',
                'string',
            ],
            'provider' => [
                'nullable',
                'string',
            ],
            'providerid' => [
                'nullable',
                'string',
            ],
            'bio' => [
                'nullable',
                'string',
            ],
            'domain' => [
                'nullable',
                'string',
            ],
            'link' => [
                'nullable',
                'string',
            ],
            'orcid' => [
                'nullable',
                'string',
            ],
            'contact_feedback' => [
                'required',
                'boolean',
            ],
            'contact_news' => [
                'required',
                'boolean',
            ],
            'mongo_id' => [
                'integer',
            ],
            'mongo_object_id' => [
                'nullable', 'string',
            ],
            'terms' => [
                'boolean',
            ],
            'is_nhse_sde_approval' => [
                'boolean',
            ],
        ];
    }
}
