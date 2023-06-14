<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class UpdateDataUseRegisterRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'counter' => [
                'int',
                'required',
            ],
            'user_id' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'team_id' => [
                'int',
                'required',
                'exists:teams,id',
            ],
        ];
    }
}
