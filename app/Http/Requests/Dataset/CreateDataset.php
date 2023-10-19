<?php

namespace App\Http\Requests\Dataset;

use App\Models\Dataset;
use App\Http\Requests\BaseFormRequest;
use App\Rules\CheckMauroFolderIdInTeam;

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
                function ($attribute, $value, $fail) {
                    $exists = Dataset::where('label', '=', $value)->where('team_id', '=', $this->team_id)->exists();

                    if ($exists) {
                        $fail('The selected dataset label exists.');
                    }
                },
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
