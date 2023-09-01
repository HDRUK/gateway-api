<?php

namespace App\Http\Requests\AuditLog;

use Config;
use App\Http\Requests\BaseFormRequest;

class CreateAuditLog extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'description' => [
                'required',
                'string',
                'max:1024',
            ],
            'function' => [
                'required',
                'string',
                'max:128',
            ],
        ];
    }

    /**
     * Provides informational messages based on the invalid request
     * parameters.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => Config::get('strings.required'),
            'description.required' => Config::get('strings.required'),
            'function.required' => Config::get('strings.required'),

            'description.string' => Config::get('strings.string'),
            'function.string' => Config::get('strings.string'),

            'user_id.numeric' => Config::get('strings.numeric'),

            'description.max' => Config::get('strings.max'),
            'function.max' => Config::get('strings.max'),

            'user_id.exists'  => Config::get('strings.exists'),
        ];
    }
}
