<?php

namespace App\Http\Requests;

use Config;

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
            'enabled.required' => Config::get('strings.required'),
            'notification_email.required' => Config::get('strings.required'),
            'outbound_auth_type.required' => Config::get('strings.required'),
            'outbound_auth_key.required' => Config::get('strings.required'),
            'outbound_endpoints_base_url.required' => Config::get('strings.required'),
            'outbound_endpoints_enquiry.required' => Config::get('strings.required'),
            'outbound_endpoints_5safes.required' => Config::get('strings.required'),
            'outbound_endpoints_5safes_files.required' => Config::get('strings.required'),
            'inbound_service_account_id.required' => Config::get('strings.required'),

            'notification_email.max' => Config::get('strings.max'),
            'outbound_auth_type.max' => Config::get('strings.max'),
            'outbound_auth_key.max' => Config::get('strings.max'),
            'outbound_endpoints_base_url.max' => Config::get('strings.max'),
            'outbound_endpoints_enquiry.max' => Config::get('strings.max'),
            'outbound_endpoints_5safes.max' => Config::get('strings.max'),
            'outbound_endpoints_5safes_files.max' => Config::get('strings.max'),
            'inbound_service_account_id.max' => Config::get('strings.max'),

            'enabled.boolean' => Config::get('strings.boolean'),
            'notification_email.string' => Config::get('strings.string'),
            'outbound_auth_type.string' => Config::get('strings.string'),
            'outbound_auth_key.string' => Config::get('strings.string'),
            'outbound_endpoints_base_url.string' => Config::get('strings.string'),
            'outbound_endpoints_enquiry.string' => Config::get('strings.string'),
        ];
    }
}