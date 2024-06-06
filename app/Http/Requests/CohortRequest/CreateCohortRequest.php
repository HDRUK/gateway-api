<?php

namespace App\Http\Requests\CohortRequest;

use App\Http\Requests\BaseFormRequest;

class CreateCohortRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'details' => [
                'required',
                'string',
            ],
        ];
    }
}
