<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class EditReview extends BaseFormRequest
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
                'exists:tools,id',
            ],
            'user_id' => [
                'int',
                'exists:users,id',
            ],
            'rating' => [
                'int',
            ],
            'review_text' => [
                'string',
            ],
            'review_state' => [
                'string',
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
