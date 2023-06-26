<?php

namespace App\Http\Requests\Review;

use App\Http\Requests\BaseFormRequest;

class UpdateReview extends BaseFormRequest
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
                'exists:reviews,id',
            ],
            'tool_id' => [
                'int',
                'required',
                'exists:tools,id',
            ],
            'user_id' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'rating' => [
                'int',
                'required',
            ],
            'review_text' => [
                'string',
                'required',
            ],
            'review_state' => [
                'string',
                'required',
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
        $this->merge(['id' => $this->route('id')]);
    }
}
