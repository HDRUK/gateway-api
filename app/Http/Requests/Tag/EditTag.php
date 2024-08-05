<?php

namespace App\Http\Requests\Tag;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class EditTag extends BaseFormRequest
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
                'unique:tags,id',
            ],
            'type' => [
                'string',
            ],
            'description' => [
                'string',
                Rule::unique('tags')->where(function ($query) {
                    $query->where('description', trim($this->type));
                }),
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
