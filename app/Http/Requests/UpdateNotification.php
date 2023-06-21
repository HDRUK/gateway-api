<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class UpdateNotification extends BaseFormRequest
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
