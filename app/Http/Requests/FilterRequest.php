<?php

namespace App\Http\Requests;

use Config;

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
            'enabled.required' => Config::get('strings.required'),
            'type.required' => Config::get('strings.required'),
            'value.required' => Config::get('strings.required'),
            'keys.required' => Config::get('strings.required'),
        ];
    }
}
