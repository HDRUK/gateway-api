<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

class CreateTagRequest extends FormRequest
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
                function ($attribute, $value, $fail) {
                    $exists = Tag::where('type', $value)
                    ->exists();

                    if ($exists) {
                        $fail('The selected tag value already exists.');
                    }
                },
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
