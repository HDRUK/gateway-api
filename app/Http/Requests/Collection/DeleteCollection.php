<?php

namespace App\Http\Requests\Collection;

use App\Models\Collection;
use App\Http\Requests\BaseFormRequest;

class DeleteCollection extends BaseFormRequest
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
                'required',
                function ($attribute, $value, $fail) {
                    $exists = Collection::where('id', $value)->exists();

                    if (!$exists) {
                        $fail('The selected collection does not exists.');
                    }
                },
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
