<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class CreateReview extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'tool_id' => [
                'required',
                'int',
                'exists:tools,id',
            ],
            'user_id' => [
                'required',
                'int',
                'exists:users,id',
            ],
            'rating' => [
                'required',
                'int',
            ],
            'review_text' => [
                'required',
                'string',
            ],
            'review_state' => [
                'required',
                'string',
            ],
        ];
    }
}
