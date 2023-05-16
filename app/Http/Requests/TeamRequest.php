<?php

namespace App\Http\Requests;

use Config;

use Illuminate\Foundation\Http\FormRequest;

class TeamRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'allows_messaging' => [
                'required',
                'boolean',
            ],
            'workflow_enabled' => [
                'required',
                'boolean',
            ],
            'access_requests_management' => [
                'required',
                'boolean',
            ],
            'uses_5_safes' => [
                'required',
                'boolean',
            ],
            'is_admin' => [
                'required',
                'boolean',
            ],
            'member_of' => [
                'required',
                'integer',
            ],
            'contact_point' => [
                'required',
                'string',
                'max:128',
            ],
            'application_form_updated_by' => [
                'required',
                'string',
                'max:128',
            ],
            'application_form_updated_on' => [
                'required',
                'string',
                'max:19',
            ],
        ];
    }

    /**
     * Provides informational messages based on invalid request
     * parameters.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => Config::get('strings.required'),
            'enabled.required' => Config::get('strings.required'),
            'allows_messaging.required' => Config::get('strings.required'),
            'workflow_enabled.required' => Config::get('strings.required'),
            'access_requests_management.required' => Config::get('strings.required'),
            'uses_5_safes.required' => Config::get('strings.required'),
            'is_admin.required' => Config::get('strings.required'),
            'member_of.required' => Config::get('strings.required'),
            'contact_point.required' => Config::get('strings.required'),
            'application_form_updated_by.required' => Config::get('strings.required'),
            'application_form_updated_on.required' => Config::get('strings.required'),
            
            'name.string' => Config::get('strings.string'),
            'contact_point.string' => Config::get('strings.string'),
            'application_form_updated_by.string' => Config::get('strings.string'),
            'application_form_updated_on.string' => Config::get('strings.string'),

            'name.max' => Config::get('strings.max'),
            'contact_point.max' => Config::get('strings.max'),
            'application_form_updated_by.max' => Config::get('strings.max'),
            'application_form_updated_on.max' => Config::get('strings.max'),

            'enabled.boolean' => Config::get('strings.boolean'),
            'allows_messaging.boolean' => Config::get('strings.boolean'),
            'workflow_enabled.boolean' => Config::get('strings.boolean'),
            'access_requests_management.boolean' => Config::get('strings.boolean'),
            'uses_5_safes.boolean' => Config::get('strings.boolean'),
            'is_admin.boolean' => Config::get('strings.boolean'),

            'member_of.numeric' => Config::get('strings.numeric'),
        ];
    }
}
