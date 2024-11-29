<?php

namespace App\Http\Requests\CollectionIntegration;

use App\Http\Requests\BaseFormRequest;

class EditCollectionIntegration extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'int',
                'required',
                'exists:collections,id',
            ],
            'name' => [
                'string',
            ],
            'description' => [
                'string',
            ],
            'image_link' => [
                'nullable',
                'string',
            ],
            'enabled' => [
                'boolean',
            ],
            'public' => [
                'boolean',
            ],
            'datasets' => [
                'array',
            ],
            'datasets.*.id'  => [
                'integer',
                'exists:datasets,id',
            ],
            'datasets.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'datasets.*.user_id'  => [
                'integer',
                'exists:users,id',
            ],
            'datasets.*.reason'  => [
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
            'tools' => [
                'array',
            ],
            'tools.*.id'  => [
                'integer',
                'exists:tools,id',
            ],
            'tools.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'tools.*.user_id'  => [
                'integer',
                'exists:users,id',
            ],
            'tools.*.reason'  => [
                'nullable',
                'string',
            ],
            'keywords' => [
                'array',
            ],
            'keywords.*' => [
                'string',
                'distinct',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'collaborators' => [
                'array',
            ],
            'collaborators.*' => [
                'integer',
                'distinct',
                'exists:users,id',
            ],
            'team_id' => [
                'integer',
                'exists:teams,id',
            ],
            'counter' => [
                'integer'
            ],
            'mongo_id' => [
                'integer',
            ],
            'mongo_object_id' => [
                'nullable',
                'string',
            ],
            'created_at' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'updated_at' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'updated_on' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
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
        $this->merge(['id' => $this->route('id')]);
    }
}
