<?php

namespace App\Http\Requests\DataAccessApplicationReview;

use App\Http\Requests\BaseFormRequest;

class GetUserDataAccessApplicationReviewFile extends BaseFormRequest
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
            'reviewId' => [
                'int',
                'required',
                'exists:dar_application_reviews,id',
            ],
            'fileId' => [
                'string',
                'required',
                'exists:uploads,uuid',
            ],
            'userId' => [
                'int',
                'required',
                'exists:users,id'
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
            'userId' => $this->route('userId'),
            'reviewId' => $this->route('reviewId'),
            'fileId' => $this->route('fileId'),
        ]);
    }
}
