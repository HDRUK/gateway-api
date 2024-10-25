<?php

namespace App\Http\Requests\Federation;

use App\Models\TeamHasFederation;
use App\Http\Requests\BaseFormRequest;

class EditFederation extends BaseFormRequest
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
            'federation_id' => [
                'required',
                'int',
                'exists:federations,id',
                function ($attribute, $value, $fail) {
                    $exists = TeamHasFederation::where('federation_id', $value)
                        ->where('team_id', $this->team_id)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected federation is not part of the specified team.');
                    }
                },
            ],
            'federation_type' => [
                'string',
            ],
            'auth_type' => [
                'string',
                'in:API_KEY,BEARER,NO_AUTH',
            ],
            'auth_secret_key' => [
                'string',
            ],
            'endpoint_baseurl' => [
                'string',
                'url',
            ],
            'endpoint_datasets' => [
                'string',
            ],
            'endpoint_dataset' => [
                'string',
            ],
            'run_time_hour' => [
                'int',
                'between:0,23',
            ],
            'enabled' => [
                'boolean',
            ],
            'notifications' => [
                'array',
            ],
            'notifications.*' => [
                'int',
            ],
            'tested' => [
                'boolean',
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
        $this->merge([
            'team_id' => $this->route('teamId'),
            'federation_id' => $this->route('federationId'),
        ]);
    }
}
