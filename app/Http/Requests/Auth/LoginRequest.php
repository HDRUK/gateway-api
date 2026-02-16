<?php

namespace App\Http\Requests\Auth;

use Config;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class LoginRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $allowedProviders = [
            Config::get('constants.provider.service'),
            Config::get('constants.provider.cruk'),
        ];

        return [
            'email' => [
                'required',
                'string',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
            'provider' => [
                'nullable',
                'string',
                Rule::in($allowedProviders),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'password.required' => 'The password field is required.',
            'provider.in' => 'The selected provider is invalid.',
        ];
    }
}

