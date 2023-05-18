<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
                'unique:users,email',
            ],
            'password' => [
                'required', 'string',
            ],
            'sector_id' => [
                'required', 'integer',
                'exists:sectors,id',
            ],
            'organisation' => [
                'string',
            ],
            'bio' => [
                'string',
            ],
            'domain' => [
                'string',
            ],
            'link' => [
                'string',
            ],
            'orcid' => [
                'integer',
            ],
            'contact_feedback' => [
                'required', 'boolean',
            ],
            'contact_news' => [
                'required', 'boolean',
            ],
        ];
    }
}
