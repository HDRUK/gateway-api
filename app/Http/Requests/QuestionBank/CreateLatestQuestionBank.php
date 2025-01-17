<?php

namespace App\Http\Requests\QuestionBank;

use App\Http\Requests\BaseFormRequest;

class CreateLatestQuestionBank extends BaseFormRequest
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
                'integer',
            ],
            'question_type' => [
                'string',
                'in:STANDARD,CUSTOM',
            ],
            'team_id' => [
                'array'
            ],
            'team_id.*' => [
                'integer'
            ],
            'options' => [
                'required',
                'array',
            ],
            'component' => [
                'required',
                'string',
            ],
            'validations' => [
                'required',
                'array',
            ],
        ];
    }
}
