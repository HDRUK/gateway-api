<?php

namespace App\Http\Requests;

use App\Models\Permission;
use App\Models\TeamHasUser;
use App\Http\Requests\BaseFormRequest;

class UpdateTeamUserRequest extends BaseFormRequest
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
                'required',
                'exists:teams,id',
            ],
            'userId' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $exists = TeamHasUser::where('user_id', $value)
                        ->where('team_id', $this->teamId)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected user is not a member of the specified team.');
                    }
                },
            ],
            'permissions' => [
                'required',
                'array',
            ],
            'permissions.*' => [
                'required',
                function ($attribute, $value, $fail) {
                    $inputKey = explode('.', $attribute);
                    $exists = Permission::where('role', $inputKey[1])
                        ->first();

                    if (!$exists) {
                        $fail('One or more of the roles in the permissions field do not exist.');
                    }
                }
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
        $this->merge(['userId' => $this->route('userId')]);
    }
}
