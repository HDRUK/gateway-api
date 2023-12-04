<?php

namespace App\Http\Requests\TeamUser;

use App\Models\Role;
use App\Models\TeamHasUser;
use App\Http\Requests\BaseFormRequest;

class UpdateBulkTeamUser extends BaseFormRequest
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
            'payload_data' => [
                'required',
                'array',
                'min:1',
            ],
            'payload_data.*.userId' =>[
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
            'payload_data.*.roles' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    foreach ($value as $perm => $status) {
                        $exists = Role::where('name', $perm)->first();

                        if (!$exists) {
                            $fail('The role `' . $perm . '` does not exist in the roles table.');
                            break;
                        }
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
        $payloadData = json_decode($this->getContent(), true);

        $this->merge(['teamId' => $this->route('teamId')]);
        $this->merge(['payload_data' => $payloadData]); }
}
