<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class CreateDARIntegration extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
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
}
