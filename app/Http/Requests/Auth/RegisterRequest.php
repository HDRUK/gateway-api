<?php

namespace App\Http\Requests\Auth;

use Config;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $provider = $this->input('provider', Config::get('constants.provider.service'));
        $allowedProviders = [
            Config::get('constants.provider.service'),
            Config::get('constants.provider.cruk'),
        ];

        return [
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users')->where(function ($query) use ($provider) {
                    return $query->where('email', $this->email)->where('provider', $provider);
                }),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'firstname' => [
                'nullable',
                'string',
                'max:255',
            ],
            'lastname' => [
                'nullable',
                'string',
                'max:255',
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
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }
}

