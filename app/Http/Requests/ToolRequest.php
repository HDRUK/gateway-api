<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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
