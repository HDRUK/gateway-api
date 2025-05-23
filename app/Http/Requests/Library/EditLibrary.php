<?php

namespace App\Http\Requests\Library;

use App\Http\Requests\BaseFormRequest;

class EditLibrary extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'int',
                'exists:users,id',
            ],
            'dataset_id' => [
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
        $this->merge(['id' => $this->route('id')]);
    }
}
