<?php

namespace App\Http\Requests\V3\Dataset;

use App\Http\Requests\BaseFormRequest;

class CreateDataset extends BaseFormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
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
                'required',
                'string',
                'in:ACTIVE,ARCHIVED,DRAFT',
            ],
            'is_cohort_discovery' => [
                'boolean',
            ],
        ];
    }
}
