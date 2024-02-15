<?php

namespace App\Http\Requests\Filter;

use App\Http\Requests\BaseFormRequest;

class UpdateFilter extends BaseFormRequest
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
                'int',
                'exists:filters,id',
            ],
            'type' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $type = ['dataset', 'collection', 'tool', 'course', 'project', 'paper', 'dataUseRegister'];

                    if (!in_array($value, $type)) {
                        $fail('The selected value is invalid.');
                    }
                },
            ],
            'keys' => [
                'required',
                'string',
            ],
            'enabled' => [
                'required',
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
