<?php

namespace App\Http\Requests\UserRole;

use App\Http\Requests\BaseFormRequest;

class CreateUserRole extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'userId' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'roles' => [
                'required',
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
        $this->merge(['userId' => $this->route('userId')]);
    }
}
