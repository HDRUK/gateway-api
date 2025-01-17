<?php

namespace App\Http\Requests\QuestionBank;

use App\Http\Requests\BaseFormRequest;

class UpdateLatestQuestionBank extends BaseFormRequest
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
            'question_type' => [
                'string',
                'in:STANDARD,CUSTOM',
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
                'integer',
            ],
            'team_id' => [
                'array'
            ],
            'team_id.*' => [
                'integer'
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
