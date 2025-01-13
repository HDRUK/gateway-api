<?php

namespace App\Http\Requests\DataAccessTemplate;

use App\Http\Requests\BaseFormRequest;

class CreateDataAccessTemplate extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'team_id' => [
                'required',
                'integer',
                'exists:teams,id',
            ],
            'published' => [
                'boolean',
            ],
            'locked' => [
                'boolean',
            ],
        ];
    }
}
