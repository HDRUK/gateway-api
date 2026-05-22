<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Validator;

class PublicationSearch extends BaseFormRequest
{
    /**
     * Add Query parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['source' => $this->query('source')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $queryRules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[\p{L}\p{N}\s\-\.\,\:\/\%\'\"\(\)\#\=\&\+]+$/u',
            'not_regex:/(::char|::integer|char\s*\(|0x[0-9a-f]+|\/\*.*\*\/|xp_\w+)/i',
        ];

        return [
            'query' => [
                'nullable',
                function ($attribute, $value, $fail) use ($queryRules) {
                    if (is_string($value)) {
                        $validator = Validator::make(
                            [$attribute => $value],
                            [$attribute => $queryRules]
                        );

                        if ($validator->fails()) {
                            $fail($validator->errors()->first($attribute));
                        }
                    } elseif (is_array($value)) {
                        foreach ($value as $index => $item) {
                            $validator = Validator::make(
                                [$attribute => $item],
                                [$attribute => $queryRules]
                            );

                            if ($validator->fails()) {
                                $fail("query[{$index}]: " . $validator->errors()->first($attribute));
                            }
                        }
                    } else {
                        $fail('The query must be a string or an array of strings.');
                    }
                },
            ],
            'source' => [
                'nullable',
                'string',
                'in:GAT,FED',
            ],
            'page' => 'integer',
            'per_page' => 'integer',
            'sort' => [
                'regex:/^(projectTitle|created_at|year_of_publication|updated_at|name|score|date|title):(asc|desc)$/i',
                'nullable'
            ],
        ];
    }
}
