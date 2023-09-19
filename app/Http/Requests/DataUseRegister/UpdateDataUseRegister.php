<?php

namespace App\Http\Requests\DataUseRegister;

use App\Models\TeamHasUser;
use App\Http\Requests\BaseFormRequest;

class UpdateDataUseRegister extends BaseFormRequest
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
                'exists:data_use_registers,id',
            ],
            'dataset_id' => [
                'required',
                'int',
                'exists:datasets,id'
            ],
            'enabled' => [
                'nullable',
                'boolean',
            ],
            'user_id' => [
                'int',
                'exists:users,id',
            ],
            'ro_crate' => [
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
