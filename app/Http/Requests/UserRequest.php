<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'firstname' => [
                'required', 'string',
            ],
            'lastname' => [
                'required', 'string',
            ],
            'email' => [
                'required', 'string', 'email',
                Rule::unique('users')->where(function ($query) {
                    $query->where('email', trim($this->email));
                }),
            ],
            'password' => [
                'required', 'string',
            ],
        ];
    }
}
