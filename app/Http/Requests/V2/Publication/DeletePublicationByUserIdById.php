<?php

namespace App\Http\Requests\V2\Publication;

use App\Http\Requests\BaseFormRequest;

class DeletePublicationByUserIdById extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'userId' => 'required|int|exists:users,id',
            'id' => [
                'required',
                'int',
                'exists:publications,id',
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
            'userId' => $this->route('userId'),
        ]);
    }
}
