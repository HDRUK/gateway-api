<?php

namespace App\Http\Requests\TeamUser;

use App\Http\Requests\BaseFormRequest;

class CreateTeamUser extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'teamId' => [
                'int', 
                'required',
                'exists:teams,id',
            ],
            'userId' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'permissions' => [
                'required',
                'array',
                'exists:permissions,role',
            ],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
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
        $this->merge(['teamId' => $this->route('teamId')]);
    }
}
