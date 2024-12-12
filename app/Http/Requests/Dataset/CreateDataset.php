<?php

namespace App\Http\Requests\Dataset;

use App\Http\Requests\BaseFormRequest;

class CreateDataset extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => [
                'int',
                'exists:teams,id',
            ],
            'user_id' => [
                'int',
                'exists:users,id',
            ],
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
}
