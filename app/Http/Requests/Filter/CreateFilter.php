<?php

namespace App\Http\Requests\Filter;

use App\Http\Requests\BaseFormRequest;

class CreateFilter extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
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
}
