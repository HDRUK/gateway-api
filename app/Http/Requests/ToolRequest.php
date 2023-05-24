<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class ToolRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'mongo_object_id' => [
                'string',
            ],
            'name' => [
                'required', 'string',
            ],
            'url' => [
                'nullable', 'string',
            ],
            'description' => [
                'required', 'string',
            ],
            'license' => [
                'nullable', 'string',
            ],
            'tech_stack' => [
                'nullable', 'string',
            ],
            'user_id' => [
                'required', 'integer'
            ],
            'tag' => [
                'nullable', 'array', 
            ],
            'tag.*' => [
                'integer',
            ],
            'enabled' => [
                'required', 'boolean',
            ],
        ];
    }
}
