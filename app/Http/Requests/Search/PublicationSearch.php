<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class PublicationSearch extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'query' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Allow empty
                    if (is_null($value) || $value === '') {
                        return;
                    }
                    // Allow string (alphanumeric)
                    if (is_string($value) && preg_match('/^[a-zA-Z0-9\s]*$/', $value)) {
                        return;
                    }
                    // Allow array of alphanumeric strings
                    if (is_array($value)) {
                        foreach ($value as $item) {
                            if (!is_string($item) || !preg_match('/^[a-zA-Z0-9\s]*$/', $item)) {
                                $fail('The '.$attribute.' must be alphanumeric or an array of alphanumeric strings.');
                                return;
                            }
                        }
                        return;
                    }
                    // Allow URL
                    if (filter_var($value, FILTER_VALIDATE_URL)) {
                        return;
                    }
                    // If none of the above, fail
                    $fail('The '.$attribute.' must be a string, an array, a URL, or empty.');
                },
                'max:255',
            ],
            'source' => [
                'nullable',
                'string',
                'in:GAT,FED',
            ],
        ];
    }

    /**
     * Add Query parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['source' => $this->query('source')]);
    }
}
