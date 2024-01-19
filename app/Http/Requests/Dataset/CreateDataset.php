<?php

namespace App\Http\Requests\Dataset;

use App\Models\Dataset;
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
                'required',
                'exists:teams,id',
            ],
            'user_id' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'metadata' => [
                'required',
            ],
            'create_origin' => [
                'string',
                'required',
                'in:MANUAL,API,FMA',
            ],
            'status' => [
                'string',
                'required',
                'in:ACTIVE,ARCHIVED,DRAFT',
            ],
        ];
    }
}
