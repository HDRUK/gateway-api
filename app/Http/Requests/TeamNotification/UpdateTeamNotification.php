<?php

namespace App\Http\Requests\TeamNotification;

use App\Models\TeamHasNotification;
use App\Http\Requests\BaseFormRequest;

class UpdateTeamNotification extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => [
                'int',
                'required',
                'exists:teams,id',
            ],
            'notification_id' => [
                'int',
                'required',
                'exists:notifications,id',
                function ($attribute, $value, $fail) {
                    $exists = TeamHasNotification::where('notification_id', $value)
                        ->where('team_id', $this->team_id)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected notification is not part of the specified team.');
                    }
                },
            ],
            'notification_type' => [
                'required',
                'string',
            ],
            'message' => [
                'required',
                'string',
            ],
            'opt_in' => [
                'required',
                'boolean',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'email' => [
                'required',
                'email',
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
        $this->merge([
            'team_id' => $this->route('teamId'),
            'notification_id' => $this->route('notificationId'),
        ]);
    }
}
