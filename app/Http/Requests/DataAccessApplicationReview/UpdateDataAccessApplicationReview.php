<?php

namespace App\Http\Requests\DataAccessApplicationReview;

use App\Http\Requests\BaseFormRequest;

class UpdateDataAccessApplicationReview extends BaseFormRequest
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
            'reviewId' => [
                'int',
                'required',
                'exists:dar_application_reviews,id',
            ],
            'comment' => [
                'string',
                'required',
            ]
            ,
            'user_id' => [
                'int',
                'exists:users,id'
            ],
            'team_id' => [
                'int',
                'exists:teams,id'
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
            'reviewId' => $this->route('reviewId'),
        ]);
    }
}
