<?php

namespace App\Http\Requests\Collection;

use App\Models\Keyword;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class CreateCollection extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'required',
                Rule::unique('collections')->where(function ($query) {
                    $query->where('name', trim($this->name));
                }),
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
            'public' => [
                'required',
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
}
