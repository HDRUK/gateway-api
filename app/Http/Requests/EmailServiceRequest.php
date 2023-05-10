<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailServiceRequest extends FormRequest
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
            'to' => [
                'required',
                'integer',
            ],
            'identifier' => [
                'required',
                'string',
                'max:255',
            ],
            'replacements' => [
                'required',
                'array',
            ],
        ];
    }

    /**
     * Provides informational messages based on the invalid request
     * parameters.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.required' => 'the parameter ":attribute" is required',
            'identifier.required' => 'the parameter ":attribute" is required',
            'replacements.required' => 'the parameter ":attribute" is required',

            'identifier.max' => 'the parameter ":attribute" must not exceed :max characters',

            'to.numeric' => 'the parameter ":attribute" must be an integer',
        ];
    }
}
