<?php

namespace App\Http\Requests\DarIntegration;

use App\Http\Requests\BaseFormRequest;

class EditDARIntegration extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'exists:dar_integrations,id',
            ],
            'enabled' => [
                'integer',
            ],
            'notification_email' => [
                'string',
                'max:255',
            ],
            'outbound_auth_type' => [
                'string',
                'max:255',
            ],
            'outbound_auth_key' => [
                'string',
                'max:255',
            ],
            'outbound_endpoints_base_url' => [
                'string',
                'max:255',
            ],
            'outbound_endpoints_enquiry' => [
                'string',
                'max:255',
            ],
            'outbound_endpoints_5safes' => [
                'string',
                'max:255',
            ],
            'outbound_endpoints_5safes_files' => [
                'string',
                'max:255',
            ],
            'inbound_service_account_id' => [
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
