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
                'string',
                'max:255',
            ],
            'publication_type' => [
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
