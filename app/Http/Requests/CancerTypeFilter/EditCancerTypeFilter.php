<?php

namespace App\Http\Requests\CancerTypeFilter;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class EditCancerTypeFilter extends BaseFormRequest
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
                'sometimes',
                'string',
                'max:255',
                'regex:/^[0-9]+(_[0-9]+)*$/',
                Rule::unique('cancer_type_filters', 'filter_id')->ignore($this->route('filter_id'), 'filter_id'),
            ],
            'label' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'category' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'primary_group' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'count' => [
                'sometimes',
                'regex:/^[0-9]+$/',
            ],
            'parent_id' => [
                'sometimes',
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
