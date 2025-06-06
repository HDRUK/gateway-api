<?php

namespace App\Http\Requests\Permission;

use App\Http\Requests\BaseFormRequest;

class UpdatePermission extends BaseFormRequest
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
                'exists:permissions,id',
            ],
            'name' => [
                'required',
                'string',
                'unique:permissions,name',
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
