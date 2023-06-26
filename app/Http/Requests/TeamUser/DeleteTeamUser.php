<?php

namespace App\Http\Requests\TeamUser;

use App\Http\Requests\BaseFormRequest;

class DeleteTeamUser extends BaseFormRequest
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
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([ 'teamId' => $this->route('teamId') ]);
        $this->merge([ 'userId' => $this->route('userId') ]);
    }
}
