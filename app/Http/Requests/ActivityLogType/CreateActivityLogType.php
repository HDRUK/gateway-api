<?php

namespace App\Http\Requests\ActivityLogType;

use App\Http\Requests\BaseFormRequest;

class CreateActivityLogType extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
            ],
        ];
    }
}
