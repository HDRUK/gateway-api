<?php

namespace App\Http\Requests\QuestionBank;

use App\Http\Requests\BaseFormRequest;

class CreateQuestionBank extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'section_id' => [
                'required',
                'integer',
                'exists:dar_sections,id',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'force_required' => [
                'required',
                'boolean',
            ],
            'allow_guidance_override' => [
                'required',
                'boolean',
            ],
            'title' => [
                'required',
                'string',
            ],
            'guidance' => [
                'required',
                'string',
            ],
            'required' => [
                'boolean',
            ],
            'default' => [
                'required',
                'boolean',
            ],
            'question_type' => [
                'string',
                'in:STANDARD,CUSTOM',
            ],
            'team_ids' => [
                'array'
            ],
            'team_ids.*' => [
                'integer'
            ],
            'options' => [
                'array',
            ],
            'component' => [
                'required',
                'string',
            ],
            'validations' => [
                'array',
            ],
            'all_custodians' => [
                'boolean',
            ]
        ];
    }
}
