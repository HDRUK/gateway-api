<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class EditTest extends BaseFormRequest
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
                'int',
                'required',
                'exists:teams,id',
            ],
            'name' => [
                'string',
            ],
            'enabled' => [
                'boolean',
            ],
            'allows_messaging' => [
                'boolean',
            ],
            'workflow_enabled' => [
                'boolean',
            ],
            'access_requests_management' => [
                'boolean',
            ],
            'uses_5_safes' => [
                'boolean',
            ],
            'is_admin' => [
                'boolean',
            ],
            'member_of' => [
                'integer',
            ],
            'contact_point' => [
                'string',
            ],
            'application_form_updated_by' => [
                'string',
            ],
            'application_form_updated_on' => [
                'string',
            ],
            'notifications' => [
                'array',

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
