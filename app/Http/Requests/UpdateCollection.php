<?php

namespace App\Http\Requests;

use App\Models\Collection;
use App\Http\Requests\BaseFormRequest;

class UpdateCollection extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'int',
                'required',
                function ($attribute, $value, $fail) {
                    $exists = Collection::withTrashed()
                        ->where('id', $value)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected collection does not exists.');
                    }
                },
            ],
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
