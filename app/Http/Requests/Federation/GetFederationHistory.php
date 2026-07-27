<?php

namespace App\Http\Requests\Federation;

use App\Http\Requests\BaseFormRequest;
use App\Models\TeamHasFederation;

class GetFederationHistory extends BaseFormRequest
{
    /**
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
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
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
