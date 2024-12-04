<?php

namespace App\Http\Requests\Dataset;

use App\Http\Requests\BaseFormRequest;

class ExportDataset extends BaseFormRequest
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
                'exists:datasets,id',
            ],
            'download_type' => [
                'string',
                'required',
                'in:metadata,observations,structural'
            ]
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
