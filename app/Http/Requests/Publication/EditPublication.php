<?php

namespace App\Http\Requests\Publication;

use App\Http\Requests\BaseFormRequest;

class EditPublication extends BaseFormRequest
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
                'required',
                'int',
                'exists:publications,id',
            ],
            'paper_title' => [
                'string',
            ],
            'authors' => [
                'string',
            ],
            'year_of_publication' => [
                'string',
                'max:4',
            ],
            'paper_doi' => [
                'nullable',
                'string',
                'max:255',
            ],
            'publication_type' => [
                'string',
                'max:255',
            ],
            'publication_type_mk1' => [
                'string',
                'max:255',
            ],
            'journal_name' => [
                'string',
                'max:255',
            ],
            'abstract' => [
                'string',
            ],
            'url' => [
                'nullable',
                'string',
            ],
            'datasets' => [
                'nullable',
                'array',
            ],
            'datasets.*.id'  => [
                'integer',
                'exists:datasets,id',
            ],
            'datasets.*.link_type'  => [
                'string',
                'nullable',
            ],
            'datasets.*.description'  => [
                'string',
                'nullable',
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
            'mongo_id' => [
                'nullable',
                'string',
            ],
            'team_id' => [
                'nullable',
                'integer',
                'exists:teams,id',
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
