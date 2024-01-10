<?php

namespace App\Http\Requests\Collection;

use App\Models\Keyword;
use App\Models\Collection;
use App\Http\Requests\BaseFormRequest;

class EditCollection extends BaseFormRequest
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
            ],
            'description' => [
                'string',
            ],
            'image_link' => [
                'string',
                'url',
            ],
            'enabled' => [
                'boolean',
            ],
            'public' => [
                'boolean',
            ],
            'datasets' => [
                'array',
            ],
            'datasets.*'  => [
                'integer',
                'distinct',
                'exists:datasets,id',
            ],
            'keywords' => [
                'array',
            ],
            'keywords.*' => [
                'string',
                'distinct',
                function ($attribute, $value, $fail) {
                    $keywords = Keyword::where(['name' => $value, 'enabled' => 1])->first();

                    if (!$keywords) {
                        $fail('The selected keyword is invalid, not found. - ' . $value);
                    }
                }
            ],
            'userId' => [
                'integer',
                'exists:users,id',
            ],
            'counter' => [
                'integer'
            ],
            'mongo_id' => [
                'integer',
            ],
            'mongo_object_id' => [
                'nullable',
                'string',
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
