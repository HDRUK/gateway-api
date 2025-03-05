<?php

namespace App\Http\Requests\V2\Tool;

use App\Http\Requests\BaseFormRequest;

class UpdateToolByUserIdById extends BaseFormRequest
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
            'userId' => 'required|int|exists:users,id',
            'id' => [
                'int',
                'required',
                'exists:tools,id',
            ],
            'name' => [
                'required',
                'string',
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
                'regex:/^[a-z0-9\s]+$/i'
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
                'exists:type_categories,id',
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
                'sometimes',
                'string',
                'in:ACTIVE,ARCHIVED,DRAFT',
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
        $this->merge([
            'id' => $this->route('id'),
            'userId' => $this->route('userId'),
        ]);
    }
}
