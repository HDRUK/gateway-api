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
            'results_insights' => [
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
                'exists:categories,id',
            ],
            'associated_authors' => [
                'nullable',
                'string',
            ],
            'contact_address' => [
                'nullable',
                'string',
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
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'publications.*.reason'  => [
                'nullable',
                'string',
            ],
            'collections.*.id'  => [
                'integer',
                'exists:collections,id',
            ],
            'collections.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'durs.*.id'  => [
                'integer',
                'exists:dur,id',
            ],
            'durs.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'dataset.*.id'  => [
                'integer',
                'exists:datasets,id',
            ],
            'dataset.*.link_type'  => [
                'string',
            ],
            'any_dataset' => [
                'nullable',
                'boolean',
            ],
            'status' => [
                'string',
                'in:ACTIVE,ARCHIVED,DRAFT',
            ],
        ];
    }
}
