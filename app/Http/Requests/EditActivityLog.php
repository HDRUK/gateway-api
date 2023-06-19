<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class EditActivityLog extends BaseFormRequest
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
                'integer',
                'exists:activity_logs,id',
            ],
            'event_type' => [
                'string',
            ],
            'user_type_id' => [
                'integer',
                'exists:activity_log_user_types,id',
            ],
            'log_type_id' => [
                'integer',
                'exists:activity_log_types,id',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'version' => [
                'string',
            ],
            'html' => [
                'string',
            ],
            'plain_text' => [
                'string',
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
