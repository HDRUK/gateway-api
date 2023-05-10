<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class DarIntegrationRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     * 
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => [
                'required',
                'integer',
            ],
            'notification_email' => [
                'required',
                'string',
                'max:255',
            ],
            'outbound_auth_type' => [
                'required',
                'string',
                'max:255',
            ],
            'outbound_auth_key' => [
                'required',
                'string',
                'max:255',
            ],
            'outbound_endpoints_base_url' => [
                'required',
                'string',
                'max:255',
            ],
            'outbound_endpoints_enquiry' => [
                'required',
                'string',
                'max:255',
            ],
            'outbound_endpoints_5safes' => [
                'required',
                'string',
                'max:255',
            ],
            'outbound_endpoints_5safes_files' => [
                'required',
                'string',
                'max:255',
            ],
            'inbound_service_account_id' => [
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
            'enabled.required' => 'the parameter ":attribute" is required',
            'notification_email.required' => 'the parameter ":attribute" is required',
            'outbound_auth_type.required' => 'the parameter ":attribute" is required',
            'outbound_auth_key.required' => 'the parameter ":attribute" is required',
            'outbound_endpoints_base_url.required' => 'the parameter ":attribute" is required',
            'outbound_endpoints_enquiry.required' => 'the parameter ":attribute" is required',
            'outbound_endpoints_5safes.required' => 'the parameter ":attribute" is required',
            'outbound_endpoints_5safes_files.required' => 'the parameter ":attribute" is required',
            'inbound_service_account_id.required' => 'the parameter ":attribute" is required',

            'notification_email.max' => 'the parameter ":attribute" must not exceed :max characters',
            'outbound_auth_type.max' => 'the parameter ":attribute" must not exceed :max characters',
            'outbound_auth_key.max' => 'the parameter ":attribute" must not exceed :max characters',
            'outbound_endpoints_base_url.max' => 'the parameter ":attribute" must not exceed :max characters',
            'outbound_endpoints_enquiry.max' => 'the parameter ":attribute" must not exceed :max characters',
            'outbound_endpoints_5safes.max' => 'the parameter ":attribute" must not exceed :max characters',
            'outbound_endpoints_5safes_files.max' => 'the parameter ":attribute" must not exceed :max characters',
            'inbound_service_account_id.max' => 'the parameter ":attribute" must not exceed :max characters',

            'enabled.boolean' => 'the parameter ":attribute" must be a boolean',
            'notification_email.string' => 'the parameter ":attribute" must be a string',
            'outbound_auth_type.string' => 'the parameter ":attribute" must be a string',
            'outbound_auth_key.string' => 'the parameter ":attribute" must be a string',
            'outbound_endpoints_base_url.string' => 'the parameter ":attribute" must be a string',
            'outbound_endpoints_enquiry.string' => 'the parameter ":attribute" must be a string',
        ];
    }
}