<?php

namespace App\Http\Requests\DataAccessApplicationReview;

use App\Http\Requests\BaseFormRequest;

class GetDataAccessApplicationReviewFile extends BaseFormRequest
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
                'int',
                'required',
                'exists:uploads,id',
            ],
            'teamId' => [
                'int',
                'required',
                'exists:teams,id'
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
            'teamId' => $this->route('teamId'),
            'reviewId' => $this->route('reviewId'),
            'fileId' => $this->route('fileId'),
        ]);
    }
}
