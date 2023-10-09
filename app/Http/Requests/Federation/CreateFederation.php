<?php

namespace App\Http\Requests\Federation;

use App\Http\Requests\BaseFormRequest;

class CreateFederation extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => [
                'required',
                'int',
                'exists:teams,id',
            ],
            'federation_type' => [
                'required',
                'string',
            ],
            'auth_type' => [
                'required',
                'string',
                'regex:(api_key|bearer|no_auth)',
            ],
            'auth_secret_key' => [
                'required',
                'string',
            ],
            'endpoint_baseurl' => [
                'required',
                'string',
                'url',
            ],
            'endpoint_datasets' => [
                'required',
                'string',
            ],
            'endpoint_dataset' => [
                'required',
                'string',
            ],
            'run_time_hour' => [
                'required',
                'int',
                'between:0,23',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'notification' => [
                'array',
            ],
            'notification.*' => [
                'email',
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
        $this->merge(['team_id' => $this->route('teamId')]);
    }
}
