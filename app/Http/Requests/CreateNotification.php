<?php

namespace App\Http\Requests;

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
        ];
    }
}
