<?php

namespace App\Http\Requests\DataAccessApplicationReview;

use App\Http\Requests\BaseFormRequest;

class UpdateGlobalUserDataAccessApplicationReview extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'userId' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'id' => [
                'int',
                'required',
                'exists:dar_applications,id',
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
            'userId' => $this->route('userId'),
            'id' => $this->route('id'),
            'reviewId' => $this->route('reviewId'),
        ]);
    }
}
