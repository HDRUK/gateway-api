<?php

namespace App\Http\Requests\Dataset;

use App\Http\Requests\BaseFormRequest;
use App\Rules\CheckMauroFolderIdInTeam;

class UpdateDataset extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'int',
                'exists:datasets,id',
            ],
            'team_id' => [
                'int',
                'required',
                'exists:teams,id',
                new CheckMauroFolderIdInTeam,
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
            'status' => [
                'string',
                'required',
                'in:ACTIVE,ARCHIVED,DRAFT',
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
