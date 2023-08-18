<?php

namespace App\Http\Requests\Role;

use App\Models\Role;
use App\Http\Requests\BaseFormRequest;

class EditRole extends BaseFormRequest
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
                'string',
                function ($attribute, $value, $fail) {
                    $exists = Role::where('name', $value)->where('id', '<>', $this->id)->count();

                    if ($exists) {
                        $fail('The selected name already exist.');
                    }
                }
            ],
            'enabled' => [
                'boolean',
            ],
            'permissions' => [
                'array',
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
