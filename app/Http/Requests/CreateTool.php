<?php

namespace App\Http\Requests;

use App\Models\Tool;
use App\Http\Requests\BaseFormRequest;

class CreateTool extends BaseFormRequest
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
                'required', 
                'string',
                function ($attribute, $value, $fail) {
                    $exists = Tool::withTrashed()->where('name', $value)->count();

                    if ($exists) {
                        $fail('The selected name already exist.');
                    }
                },
            ],
            'url' => [
                'nullable', 
                'string',
            ],
            'description' => [
                'required', 
                'string',
            ],
            'license' => [
                'nullable', 
                'string',
            ],
            'tech_stack' => [
                'nullable', 
                'string',
            ],
            'user_id' => [
                'required', 
                'integer'
            ],
            'tag' => [
                'nullable', 
                'array', 
            ],
            'tag.*' => [
                'integer',
            ],
            'enabled' => [
                'required', 
                'boolean',
            ],
        ];
    }
}
