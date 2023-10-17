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
                'required',
                'exists:teams,id',
            ],
            'user_id' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'label' => [
                'string',
                'required',
            ],
            'short_description' => [
                'string',
                'required',
            ],
            'dataset' => [
                'required',
            ],
            'create_origin' => [
                'string',
                'required',
                'in:MANUAL,API,FMA',
            ],
        ];
    }
}
