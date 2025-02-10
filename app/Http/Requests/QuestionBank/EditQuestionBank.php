<?php

namespace App\Http\Requests\QuestionBank;

use App\Http\Requests\BaseFormRequest;

class EditQuestionBank extends BaseFormRequest
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
                'exists:question_bank_questions,id',
            ],
            'section_id' => [
                'integer',
                'exists:dar_sections,id',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'force_required' => [
                'boolean',
            ],
            'allow_guidance_override' => [
                'boolean',
            ],
            'question_type' => [
                'string',
                'in:STANDARD,CUSTOM',
            ],
            'field' => [
                'array',
            ],
            'title' => [
                'string',
            ],
            'guidance' => [
                'string',
            ],
            'required' => [
                'boolean',
            ],
            'default' => [
                'boolean',
            ],
            'team_ids' => [
                'array'
            ],
            'team_ids.*' => [
                'integer'
            ],
            'all_custodians' => [
                'boolean',
            ]
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
