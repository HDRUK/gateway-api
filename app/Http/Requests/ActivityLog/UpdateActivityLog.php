<?php

namespace App\Http\Requests\ActivityLog;

use App\Http\Requests\BaseFormRequest;

class UpdateActivityLog extends BaseFormRequest
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
                'required',
                'string',
            ],
            'user_type_id' => [
                'required',
                'exists:activity_log_user_types,id',
            ],
            'log_type_id' => [
                'required',
                'integer',
                'exists:activity_log_types,id',
            ],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'version' => [
                'required',
                'string',
            ],
            'html' => [
                'required',
                'string',
            ],
            'plain_text' => [
                'required',
                'string',
            ],
            'user_id_mongo' => [
                'required',
                'sometimes',
                'nullable',
                'string',
            ],
            'version_id_mongo' => [
                'required',
                'sometimes',
                'nullable',
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
