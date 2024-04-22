<?php

namespace App\Http\Requests\Tool;

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
                'nullable', 
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
            'team_id' => [
                'integer',
                'exists:teams,id',
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
            'programming_language' => [
                'nullable', 
                'string',
            ],
            'programming_package' => [
                'nullable', 
                'string',
            ],
            'type_category' => [
                'nullable', 
                'string',
            ],
            'associated_authors' => [
                'nullable', 
                'string',
            ],
            'contact_address' => [
                'nullable', 
                'string',
            ],
        ];
    }
}
