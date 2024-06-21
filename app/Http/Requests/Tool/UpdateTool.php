<?php

namespace App\Http\Requests\Tool;

use App\Models\Tool;
use App\Http\Requests\BaseFormRequest;

class UpdateTool extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'id' => [
                'int',
                'required',
                function ($attribute, $value, $fail) {
                    $exists = Tool::withTrashed()->where('id', $value)->count();
    
                    if (!$exists) {
                        $fail('The ID does not exist in the database');
                    }
                }
            ],
            'mongo_object_id' => [
                'string',
            ],
            'name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = Tool::withTrashed()->where('name', $value)->where('id', '<>', $id)->count();

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
                'int',
                'exists:licenses,id',
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
                'array',
            ],
            'programming_language.*' => [
                'integer',
            ],
            'programming_package' => [
                'nullable', 
                'array',
            ],
            'programming_package.*' => [
                'integer',
            ],
            'type_category' => [
                'nullable', 
                'array',
            ],
            'type_category.*' => [
                'integer',
            ],
            'associated_authors' => [
                'nullable', 
                'string',
            ],
            'contact_address' => [
                'nullable', 
                'string',
            ],
            'publications' => [
                'array',
            ],
            'publications.*.id'  => [
                'integer',
                'exists:publications,id',
            ],
            'publications.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'publications.*.user_id'  => [
                'integer',
                'exists:users,id',
            ],
            'publications.*.reason'  => [
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
