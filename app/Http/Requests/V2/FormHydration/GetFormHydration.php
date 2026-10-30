<?php

namespace App\Http\Requests\V2\FormHydration;

use App\Http\Requests\BaseFormRequest;

class GetFormHydration extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teamId' => [
                'int',
                'required',
                'exists:teams,id',
            ],
            'model' => [
                'nullable',
                'string',
                'required_with:version',
            ],
            'version' => [
                'nullable',
                'string',
                'required_with:model',
            ],
            'dataset_id' => [
                'nullable',
                'int',
                'exists:datasets,id',
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
        $this->merge(['teamId' => $this->route('teamId')]);
    }
}
