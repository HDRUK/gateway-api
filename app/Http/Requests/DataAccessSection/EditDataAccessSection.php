<?php

namespace App\Http\Requests\DataAccessSection;

use App\Http\Requests\BaseFormRequest;

class EditDataAccessSection extends BaseFormRequest
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
                'exists:dar_sections,id',
            ],
            'name' => [
                'string',
            ],
            'description' => [
                'string',
            ],
            'parent_section' => [
                'integer',
            ],
            'order' => [
                'integer',
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
