<?php

namespace App\Http\Requests\Tag;

use App\Http\Enums\TagType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Http\Requests\BaseFormRequest;

class CreateTag extends BaseFormRequest
{
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
            ],
            'description' => [
                'required',
                'string',
                Rule::unique('tags')->where(function ($query) {
                    $query->where('description', trim($this->type));
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
            'type.required' => 'A type is required',
            'type.string' => 'A type need to be string format',
        ];
    }
}
