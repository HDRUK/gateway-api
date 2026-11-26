<?php

namespace App\Http\Requests\CancerTypeFilter;

use App\Http\Requests\BaseFormRequest;

class CreateCancerTypeFilter extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'filter_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[0-9]+(_[0-9]+)*$/',
                'unique:cancer_type_filters,filter_id',
            ],
            'label' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'category' => [
                'nullable',
                'string',
                'max:255',
            ],
            'primary_group' => [
                'nullable',
                'string',
                'max:255',
            ],
            'count' => [
                'sometimes',
                'regex:/^[0-9]+$/',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:cancer_type_filters,id',
            ],
            'sort_order' => [
                'sometimes',
                'integer',
            ],
        ];
    }

    /**
     * Messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'filter_id.regex' => 'The filter_id must be underscore-separated numbers, e.g. 0_0_2_59',
        ];
    }
}
