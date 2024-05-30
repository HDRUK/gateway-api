<?php

namespace App\Http\Requests\License;

use App\Http\Requests\BaseFormRequest;
use App\Models\License;

class UpdateLicense extends BaseFormRequest
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
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $exists = License::where('code', $value)->where('id', '<>', $this->id)->count();

                    if ($exists) {
                        $fail('The selected code already exist.');
                    }
                }
            ],
            'label' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $exists = License::where('label', $value)->where('id', '<>', $this->id)->count();

                    if ($exists) {
                        $fail('The selected label already exist.');
                    }
                }
            ],
            'valid_since' => [
                'required',
                'date_format:Y-m-d', // 2017-09-13
            ],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d', // 2017-10-14
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
