<?php

namespace App\Http\Requests\QuestionBank;

use App\Http\Requests\BaseFormRequest;

class GetQuestionBankSection extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'sectionId' => [
                'int',
                'required',
                'exists:dar_sections,id',
            ],
            'teamId' => [
                'int',
                'required',
                'exists:teams,id',
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
            'sectionId' => $this->route('sectionId'),
            'teamId' => $this->route('teamId'),
        ]);
    }
}
