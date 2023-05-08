<?php

namespace App\Http\Requests;

use App\Models\TeamHasUser;
use App\Rules\PermissionRoleExists;
use Illuminate\Validation\Rule;
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
        $requestData = $this->all();

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
                new PermissionRoleExists(),
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
