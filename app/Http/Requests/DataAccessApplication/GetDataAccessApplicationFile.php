<?php

namespace App\Http\Requests\DataAccessApplication;

use App\Http\Requests\BaseFormRequest;

class GetDataAccessApplicationFile extends BaseFormRequest
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
                'exists:dar_applications,id',
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
            'fileId' => $this->route('fileId'),
        ]);
    }
}
