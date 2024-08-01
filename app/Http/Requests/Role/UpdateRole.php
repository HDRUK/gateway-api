<?php

namespace App\Http\Requests\Role;

use App\Models\Role;
use App\Http\Requests\BaseFormRequest;

class UpdateRole extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'int',
                'required',
                'exists:roles,id',
            ],
            'name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $exists = Role::where('name', $value)->where('id', '<>', $this->id)->count();

                    if ($exists) {
                        $fail('The selected name already exist.');
                    }
                }
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'permissions' => [
                'required',
                'array',
            ],
            'full_name' => [
                'nullable',
                'string',
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
