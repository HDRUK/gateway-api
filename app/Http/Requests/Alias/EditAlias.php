<?php

namespace App\Http\Requests\Alias;

use App\Http\Requests\BaseFormRequest;

class EditAlias extends BaseFormRequest
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
                'required',
                'integer',
                'exists:aliases,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:aliases,name',
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
