<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseFormRequest;

class CreateNotification extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'notification_type' => [
                'required',
                'string',
            ],
            'message' => [
                'nullable',
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
                'nullable',
                'email',
                'required_without:user_id'
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ]
        ];
    }
}
