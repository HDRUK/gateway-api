<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class UserRequest extends BaseFormRequest
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
                'required', 'string',
            ],
            'lastname' => [
                'required', 'string',
            ],
            'email' => [
                'required', 'string', 'email',
                'unique:users,email',
            ],
            'password' => [
                'nullable', 'string',
            ],
            'sector_id' => [
                'required', 'integer',
                'exists:sectors,id',
            ],
            'organisation' => [
                'nullable', 'string',
            ],
            'bio' => [
                'nullable', 'string',
            ],
            'domain' => [
                'nullable', 'string',
            ],
            'link' => [
                'nullable', 'string',
            ],
            'orcid' => [
                'nullable', 'integer',
            ],
            'contact_feedback' => [
                'required', 'boolean',
            ],
            'contact_news' => [
                'required', 'boolean',
            ],
            'mongo_id' => [
                'integer',
            ],
        ];
    }
}
