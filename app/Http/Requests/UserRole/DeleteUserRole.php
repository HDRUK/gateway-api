<?php

namespace App\Http\Requests\UserRole;

use App\Http\Requests\BaseFormRequest;

class DeleteUserRole extends BaseFormRequest
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
