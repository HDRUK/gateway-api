<?php

namespace App\Http\Requests\ProgrammingPackage;

use App\Models\ProgrammingPackage;
use App\Http\Requests\BaseFormRequest;

class UpdateProgrammingPackage extends BaseFormRequest
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
                'exists:programming_packages,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $exists = ProgrammingPackage::where('name', $value)->where('id', '<>', $this->id)->count();
                    if ($exists) {
                        $fail('The selected name already exist.');
                    }
                }
            ],
            'enabled' => [
                'required',
                'boolean',
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
