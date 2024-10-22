<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseFormRequest;

class EditNotification extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'int',
                'exists:notifications,id',
            ],
            'notification_type' => [
                'string',
            ],
            'message' => [
                'string',
            ],
            'opt_in' => [
                'boolean',
            ],
            'enabled' => [
                'boolean',
            ],
            'email' => [
                'nullable',
                'email',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ]
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
