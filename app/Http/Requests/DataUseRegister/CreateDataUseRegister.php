<?php

namespace App\Http\Requests\DataUseRegister;

use App\Models\TeamHasUser;
use App\Http\Requests\BaseFormRequest;

class CreateDataUseRegister extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'dataset_id' => [
                'required',
                'int',
                'exists:datasets,id',
            ],
            'enabled' => [
                'nullable',
                'boolean',
            ],
            'user_id' => [
                'required',
                'int',
                'exists:users,id',
            ],
            'ro_crate' => [
                'nullable',
                // 'string',
            ],
        ];
    }
}
