<?php

namespace App\Http\Requests;

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
        return [
            'name' => ['required', 'string', 'max:255'],
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
            'username.required' => 'A username is required',
            'username.email' => 'A username need to be email format',
            'password.required' => 'A password is required',
        ];
    }
}
