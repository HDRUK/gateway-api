<?php

namespace App\Http\Requests\V2\Dataset;

use App\Http\Requests\BaseFormRequest;

class TestDataset extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'metadata' => [
                'required',
            ],
        ];
    }

}
