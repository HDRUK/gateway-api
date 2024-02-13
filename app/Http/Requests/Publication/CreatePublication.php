<?php

namespace App\Http\Requests\Publication;

use App\Http\Requests\BaseFormRequest;

class CreatePublication extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'paper_title' => [
                'required',
                'string',
                'max:255',
            ],
            'authors' => [
                'required',
                'string',
            ],
            'year_of_publication' => [
                'required',
                'string',
                'max:4',
            ],
            'paper_doi' => [
                'required',
                'string',
                'max:255',
            ],
            'publication_type' => [
                'required',
                'string',
                'max:255',
            ],
            'journal_name' => [
                'required',
                'string',
                'max:255',
            ],
            'abstract' => [
                'required',
                'string',
            ],
        ];
    }
}
