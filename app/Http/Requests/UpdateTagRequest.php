<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
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
                        ->where('enabled', $this->enabled)
                        ->exists();

                    if ($exists) {
                        $fail('The selected tag, enabled and descriptions already exists.');
                    }

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
            'id' => [
                'int',
                'required',
                'exists:tags,id',
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
