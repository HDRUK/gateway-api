<?php

namespace App\Http\Requests;

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
            'name.required' => 'the parameter ":attribute" is required',
            'enabled.required' => 'the parameter ":attribute" is required',
            'allows_messaging.required' => 'the parameter ":attribute" is required',
            'workflow_enabled.required' => 'the parameter ":attribute" is required',
            'access_requests_management.required' => 'the parameter ":attribute" is required',
            'uses_5_safes.required' => 'the parameter ":attribute" is required',
            'is_admin.required' => 'the parameter ":attribute" is required',
            'member_of.required' => 'the parameter ":attribute" is required',
            'contact_point.required' => 'the parameter ":attribute" is required',
            'application_form_updated_by.required' => 'the parameter ":attribute" is required',
            'application_form_updated_on.required' => 'the parameter ":attribute" is required',
            
            'name.string' => 'the parameter ":attribute" must be a string',
            'contact_point.string' => 'the parameter ":attribute" must be a string',
            'application_form_updated_by.string' => 'the parameter ":attribute" must be a string',
            'application_form_updated_on.string' => 'the parameter ":attribute" must be a string',

            'name.max' => 'the parameter ":attribute" must not exceed :max characters',
            'contact_point.max' => 'the parameter ":attribute" must not exceed :max characters',
            'application_form_updated_by.max' => 'the parameter ":attribute" must not exceed :max characters',
            'application_form_updated_on.max' => 'the parameter ":attribute" must not exceed :max characters',

            'enabled.boolean' => 'the parameter ":attribute" must be a boolean',
            'allows_messaging.boolean' => 'the parameter ":attribute" must be a boolean',
            'workflow_enabled.boolean' => 'the parameter ":attribute" must be a boolean',
            'access_requests_management.boolean' => 'the parameter ":attribute" must be a boolean',
            'uses_5_safes.boolean' => 'the parameter ":attribute" must be a boolean',
            'is_admin.boolean' => 'the parameter ":attribute" must be a boolean',

            'member_of.integer' => 'the parameter ":attribute" must be a integer',
        ];
    }
}
