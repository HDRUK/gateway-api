<?php

namespace App\Http\Requests\DataAccessSection;

use App\Http\Requests\BaseFormRequest;

class CreateDataAccessSection extends BaseFormRequest
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
                'string',
            ],
            'description' => [
                'required',
                'string',
            ],
            'parent_section' => [
                'integer',
            ],
            'order' => [
                'required',
                'integer',
            ],
        ];
    }
}
