<?php

namespace App\Http\Requests\Filter;

use App\Models\Filter;
use App\Http\Requests\BaseFormRequest;

class EditFilter extends BaseFormRequest
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
                'int',
                'exists:filters,id',
            ],
            'type' => [
                'string',
                function ($attribute, $value, $fail) {
                    $type = \Config::get('filters.types');

                    if (!in_array($value, $type)) {
                        $fail('The selected value is invalid.');
                    }
                },
                function ($attribute, $value, $fail) {
                    $key = $this->input('keys');
                    $checkFilter = Filter::where([
                        'type' => $value,
                        'keys' => $key,
                    ])->where('id', '<>', $this->id)->first();

                    if ($checkFilter) {
                        $fail('The combination of type and key must be unique.');
                    }
                },
            ],
            'keys' => [
                'string',
            ],
            'enabled' => [
                'boolean',
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
