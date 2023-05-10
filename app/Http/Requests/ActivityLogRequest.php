<?php

namespace App\Http\Requests;

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
            'event_type.required' => 'the parameter ":attribute" is required',
            'user_type_id.required' => 'the parameter ":attribute" is required',
            'log_type_id.required' => 'the parameter ":attribute" is required',
            'user_id.required' => 'the parameter "u:attributeer_id" is required',
            'plain_text.required' => 'the parameter ":attribute" is required',

            'event_type.string' => 'the parameter ":attribute" must be a string',
            'plain_text.string' => 'the parameter ":attribute" must be a string',

            'user_type_id.numeric' => 'the parameter ":attribute" must be an integer',
            'log_type_id.numeric' => 'the parameter ":attribute" must be an integer',
            'user_id.numeric' => 'the parameter ":attribute" must be an integer',

            'event_type.max' => 'the parameter ":attribute" must not exceed :max characters',
            'plain_text.max' => 'the parameter ":attribute" must not exceed :max characters',

            'user_type_id.exists' => 'the linked id of ":attribute" must first exist before being assigned',
            'log_type_id.exists' => 'the linked id of ":attribute" must first exist before being assigned',
            'user_id.exists'  => 'the linked id of ":attribute" must first exist before being assigned',
        ];
    }
}
