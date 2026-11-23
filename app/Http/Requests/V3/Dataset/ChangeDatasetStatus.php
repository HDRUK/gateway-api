<?php

namespace App\Http\Requests\V3\Dataset;

use App\Http\Requests\BaseFormRequest;

class ChangeDatasetStatus extends BaseFormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:ACTIVE,ARCHIVED,DRAFT',
            ],
        ];
    }
}
