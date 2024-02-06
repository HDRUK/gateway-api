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
                'exists:collections,id',
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
            'datasets.*.id'  => [
                'integer',
                'exists:datasets,id',
            ],
            'datasets.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'datasets.*.user_id'  => [
                'integer',
                'exists:users,id',
            ],
            'datasets.*.reason'  => [
                'nullable',
                'string',
            ],
            'keywords' => [
                'array',
            ],
            'keywords.*' => [
                'string',
                'distinct',
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
            'created_at' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'updated_at' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'updated_on' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
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
