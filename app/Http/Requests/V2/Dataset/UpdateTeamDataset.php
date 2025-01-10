<?php

namespace App\Http\Requests\V2\Dataset;

use App\Http\Requests\BaseFormRequest;

class UpdateTeamDataset extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'metadata' => [
                'required',
            ],
            'create_origin' => [
                'string',
                'in:MANUAL,API,GMI',
            ],
            'status' => [
                'string',
                'in:ACTIVE,ARCHIVED,DRAFT',
            ],
            'is_cohort_discovery' => [
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
        $this->merge(['id' => $this->route('id')]);
    }
}
