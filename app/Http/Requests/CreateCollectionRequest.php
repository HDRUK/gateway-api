<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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
