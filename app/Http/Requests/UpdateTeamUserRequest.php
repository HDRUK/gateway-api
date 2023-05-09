<?php

namespace App\Http\Requests;

use App\Models\Permission;
use App\Models\TeamHasUser;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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
