<?php

namespace App\Http\Requests\Collection;

use App\Http\Requests\BaseFormRequest;

class CreateCollection extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'description' => [
                'string',
                'required',
            ],
            'image_link' => [
                'string',
                'required',
                'url',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'keywords' => [
                'string',
                'required',
            ],
            'public' => [
                'required',
                'boolean',
            ],
            'counter' => [
                'integer',
                'required',
            ],
        ];
    }
}
