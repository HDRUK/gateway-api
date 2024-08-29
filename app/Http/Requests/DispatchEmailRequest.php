<?php

namespace App\Http\Requests;

class DispatchEmailRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'to' => [
                'required',
                'int',
            ],
            'identifier' => [
                'required',
                'string',
            ],
            'replacements' => [
                'required',
            ],
        ];
    }
}
