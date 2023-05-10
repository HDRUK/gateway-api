<?php

namespace App\Http\Requests;

use App\Http\Enums\TagType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                new Enum(TagType::class),
                Rule::unique('tags')->where(function ($query) {
                    $query->where('type', trim($this->type));
                }),
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Messages
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            'type.required' => 'the parameter ":attribute" is required',
            'type.string' => 'the parameter ":attribute" must be a string',
        ];
    }
}
