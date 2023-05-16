<?php

namespace App\Http\Requests;

use Config;

use Illuminate\Foundation\Http\FormRequest;

class ActivityLogRequest extends FormRequest
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
            'event_type' => [
                'required',
                'string',
                'max:255',
            ],
            'user_type_id' => [
                'required',
                'int',
                'exists:activity_log_user_types,id',
            ],
            'log_type_id' => [
                'required',
                'int',
                'exists:activity_log_types,id',
            ],
            'user_id' => [
                'required',
                'int',
                'exists:users,id',
            ],
            'plain_text' => [
                'required',
                'string',
                'max:255',
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
            'event_type.required' => Config::get('strings.required'),
            'user_type_id.required' => Config::get('strings.required'),
            'log_type_id.required' => Config::get('strings.required'),
            'user_id.required' => Config::get('strings.required'),
            'plain_text.required' => Config::get('strings.required'),

            'event_type.string' => Config::get('strings.string'),
            'plain_text.string' => Config::get('strings.string'),

            'user_type_id.numeric' => Config::get('strings.numeric'),
            'log_type_id.numeric' => Config::get('strings.numeric'),
            'user_id.numeric' => Config::get('strings.numeric'),

            'event_type.max' => Config::get('strings.max'),
            'plain_text.max' => Config::get('strings.max'),

            'user_type_id.exists' => Config::get('strings.exists'),
            'log_type_id.exists' => Config::get('strings.exists'),
            'user_id.exists'  => Config::get('strings.exists'),
        ];
    }
}
