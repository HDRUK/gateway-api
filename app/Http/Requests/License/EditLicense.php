<?php

namespace App\Http\Requests\License;

use App\Models\License;
use App\Http\Requests\BaseFormRequest;

class EditLicense extends BaseFormRequest
{
    /**
         * Get the validation rules that apply to the request.
         *
         * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
         */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'int',
                'exists:licenses,id',
            ],
            'code' => [
                'string',
                function ($attribute, $value, $fail) {
                    $exists = License::where('code', $value)->where('id', '<>', $this->id)->count();

                    if ($exists) {
                        $fail('The selected code already exist.');
                    }
                }
            ],
            'label' => [
                'string',
                function ($attribute, $value, $fail) {
                    $exists = License::where('label', $value)->where('id', '<>', $this->id)->count();

                    if ($exists) {
                        $fail('The selected label already exist.');
                    }
                }
            ],
            'valid_since' => [
                'date_format:Y-m-d', // 2017-09-13
            ],
            'valid_until' => [
                'date_format:Y-m-d', // 2017-10-14
            ],
            'definition' => [
                'string',
            ],
            'origin' => [
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
