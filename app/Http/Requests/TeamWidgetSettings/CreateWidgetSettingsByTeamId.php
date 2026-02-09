<?php

namespace App\Http\Requests\TeamWidgetSettings;

use App\Http\Requests\BaseFormRequest;

class CreateWidgetSettingsByTeamId extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teamId' => [
                'int',
                'required',
                'exists:teams,id',
            ],
            'colours' => [
                'nullable',
                'array'
            ]
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['teamId' => $this->route('teamId')]);
    }

}
