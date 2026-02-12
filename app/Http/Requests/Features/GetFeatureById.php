<?php

namespace App\Http\Requests\Features;

use App\Http\Requests\BaseFormRequest;

class GetFeatureById extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'featureId' => [
                'required',
                'integer',
                'exists:features,id',
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
        $this->merge(['featureId' => $this->route('featureId')]);
    }
}
