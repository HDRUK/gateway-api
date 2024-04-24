<?php

namespace App\Http\Requests\TypeCategory;

use App\Models\TypeCategory;
use App\Http\Requests\BaseFormRequest;

class EditTypeCategory extends BaseFormRequest
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
                'exists:type_categories,id',
            ],
            'name' => [
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $exists = TypeCategory::where('name', $value)->where('id', '<>', $this->id)->count();
                    if ($exists) {
                        $fail('The selected name already exist.');
                    }
                }
            ],
            'description' => [
                'string',
                'max:1000',
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
