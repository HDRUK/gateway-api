<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
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
            'notification_type' => [
                'required',
                'string',
                'max:128',
            ],
            'message' => [
                'required',
                'string',
                'max:45',
            ],
            'opt_in' => [
                'required',
                'boolean',
            ],
            'enabled' => [
                'required',
                'boolean',
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
            'notification_type.required' => 'the parameter ":attribute" is required',
            'message.required' => 'the parameter ":attribute" is required',
            'opt_in.required' => 'the parameter ":attribute" is required',
            'enabled.required' => 'the parameter ":attribute" is required',

            'notification_type.max' => 'the parameter ":attribute" must not exceed :max characters',
            'message.max' => 'the parameter ":attribute" must not exceed :max characters',
        ];
    }
}
