<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class IndexUser extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'filterNames' => [
                'nullable',
                'string',
                'min:3',
            ],
        ];
    }

    /**
     * Add Query parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['filterNames' => $this->query('filterNames')]);
    }
}
