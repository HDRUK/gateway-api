<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;
use App\Services\Search\AdvancedQueryCompiler;
use Closure;
use InvalidArgumentException;

class Search extends BaseFormRequest
{
    // SC: removing since this removes spaces from search queries

    // protected function prepareForValidation(): void
    // {
    //     $this->merge([
    //         'query' => preg_replace('/[^a-zA-Z0-9_-]/', '', $this->input('query')),
    //     ]);
    // }

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
                'max:255',
                'regex:/^[^<>${}\x00]*$/',
                'not_regex:/::char|::integer|::text|--|\/\*|\*\/|;\s*(DROP|DELETE|INSERT|UPDATE|ALTER|SELECT|CREATE)/i',
            ],
            'sort' => [
                'regex:/^(projectTitle|updated_at|name|score|date|title):(asc|desc)$/i',
                'nullable'
            ],
            'page' => 'integer',
            'view_type' => ['nullable', 'in:full,mini'],
            'per_page' => 'integer',
            'download' => 'boolean',
            'providers' => ['nullable', 'array'],
            'providers.*' => ['string'],
            'dataSource' => ['nullable', 'string', 'max:50'],
            // An and/or rule tree; see AdvancedQueryCompiler for its shape.
            // Compiled here so a malformed tree is a 422, not empty results.
            'advanced' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail) {
                    try {
                        app(AdvancedQueryCompiler::class)->compile((string) $this->input('type'), $value);
                    } catch (InvalidArgumentException $e) {
                        $fail($e->getMessage());
                    }
                },
            ],
        ];
    }
}
