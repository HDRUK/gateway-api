<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class CreateActivityLogUserType extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
            ],
        ];
    }
}
