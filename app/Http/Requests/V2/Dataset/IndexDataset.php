<?php

namespace App\Http\Requests\V2\Dataset;

use App\Http\Requests\BaseFormRequest;

class IndexDataset extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'filterTitle' => [
                'nullable',
                'string',
                'min:3',
            ],
            'filterStatus' => [
                'nullable',
                'string',
                'in:ACTIVE,DRAFT,ARCHIVED',
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
        $this->merge(['filterTitle' => $this->query('filterTitle')]);
        $this->merge(['filterStatus' => $this->query('filterStatus')]);
    }
}
