<?php

namespace App\Http\Requests\DataAccessApplicationReview;

use App\Http\Requests\BaseFormRequest;

class CreateDataAccessApplicationReview extends BaseFormRequest
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
                'exists:dar_applications,id',
            ],
            'questionId' => [
                'int',
                'required',
                'exists:dar_application_has_questions,question_id',
            ],
            'review_comment' => [
                'string',
                'required',
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
        $this->merge([
            'id' => $this->route('id'),
            'questionId' => $this->route('questionId'),
        ]);
    }
}
