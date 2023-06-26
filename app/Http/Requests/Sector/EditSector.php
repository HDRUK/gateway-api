<?php

namespace App\Http\Requests\Sector;

use App\Http\Requests\BaseFormRequest;

class EditSector extends BaseFormRequest
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
                'exists:sectors,id',
            ],
            'name' => [
                'string',
                'max:255',
                'unique:sectors,name',
            ],
            'enabled' => [
                'boolean',
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
