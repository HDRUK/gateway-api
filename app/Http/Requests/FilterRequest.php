<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
            ],
            'value' => [
                'required',
                'string',
            ],
            'keys' => [
                'required',
                'string',
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
            'enabled.required' => 'the parameter ":attribute" is required',
            'type.required' => 'the parameter ":attribute" is required',
            'value.required' => 'the parameter ":attribute" is required',
            'keys.required' => 'the parameter ":attribute" is required',
        ];
    }
}
