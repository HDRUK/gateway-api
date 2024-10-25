<?php

namespace App\Http\Requests\Federation;

use Closure;
use App\Models\TeamHasFederation;
use App\Http\Requests\BaseFormRequest;

class UpdateFederation extends BaseFormRequest
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
                'required',
                'string',
            ],
            'auth_type' => [
                'required',
                'string',
                'in:API_KEY,BEARER,NO_AUTH',
            ],
            'auth_secret_key' => [
                'string',
                'nullable'
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
            'notifications' => [
                'array',
            ],
            'notifications.*' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    if (!(is_numeric($value) || filter_var($email, FILTER_VALIDATE_EMAIL))) {
                        $fail("The {$attribute} is invalid.");
                    }
                },
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
