<?php

namespace App\Http\Requests\QuestionBank;

use App\Http\Requests\BaseFormRequest;

class UpdateStatusQuestionBank extends BaseFormRequest
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
            'status' => [
                'required',
                'string',
                'in:lock,unlock,archive,unarchive',
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
            'status' => $this->route('status'),
        ]);
    }
}
