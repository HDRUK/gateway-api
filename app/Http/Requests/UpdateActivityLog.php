<?php

namespace App\Http\Requests;

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
            'event_type' => [
                'required',
            ],
            'user_type_id' => [
                'required',
            ],
            'log_type_id' => [
                'required',
            ],
            'user_id' => [
                'required',
            ],
            'version' => [
                'required',
            ],
            'html' => [
                'required',
            ],
            'plain_text' => [
                'required',
            ],  
        ];
    }
}
