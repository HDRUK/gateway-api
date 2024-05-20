<?php

namespace App\Http\Requests\License;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateLicense extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                Rule::unique('licenses')->where(function ($query) {
                    $query->where('code', trim($this->code));
                }),
            ],
            'label' => [
                'required',
                'string',
                Rule::unique('licenses')->where(function ($query) {
                    $query->where('label', trim($this->label));
                }),
            ],
            'valid_since' => [
                'required',
                'date_format:Y-m-d', // 2017-09-12
            ],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d', // 2017-09-12
            ],
            'definition' => [
                'required',
                'string',
            ],
            'origin' => [
                'required',
                'string',
            ],
        ];
    }
}
