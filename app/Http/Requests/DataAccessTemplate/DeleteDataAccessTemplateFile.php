<?php

namespace App\Http\Requests\DataAccessTemplate;

use App\Http\Requests\BaseFormRequest;

class DeleteDataAccessTemplateFile extends BaseFormRequest
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
                'exists:dar_templates,id',
            ],
            'teamId' => [
                'int',
                'required',
                'exists:teams,id',
            ],
            'fileId' => [
                'int',
                'required',
                'exists:uploads,id',
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
        $this->merge([
            'id' => $this->route('id'),
            'teamId' => $this->route('teamId'),
            'fileId' => $this->route('fileId')
        ]);
    }
}
