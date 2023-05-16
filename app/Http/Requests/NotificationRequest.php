<?php

namespace App\Http\Requests;

use Config;

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
            'notification_type.required' => Config::get('strings.required'),
            'message.required' => Config::get('strings.required'),
            'opt_in.required' => Config::get('strings.required'),
            'enabled.required' => Config::get('strings.required'),

            'notification_type.max' => Config::get('strings.max'),
            'message.max' => Config::get('strings.max'),
        ];
    }
}
