<?php

namespace App\Http\Requests;

use Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users')->where(function ($query) {
                    $query->where('email', $this->email)
                        ->where('provider', Config::get('constants.provider.service'));
                }),
            ],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * Messages
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.required' => 'A username is required',
            'email.email' => 'A email need to be email format',
            'password.required' => 'A password is required',
        ];
    }
}
