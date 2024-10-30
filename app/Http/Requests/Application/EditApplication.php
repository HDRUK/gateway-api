<?php

namespace App\Http\Requests\Application;

use Closure;
use App\Http\Requests\BaseFormRequest;

class EditApplication extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'id' => [
                'required',
                'exists:applications,id',
            ],
            'name' => [
                'string',
            ],
            'image_link' => [
                'string',
                'nullable',
                'url',
            ],
            'description' => [
                'string',
            ],
            'team_id' => [
                'integer',
                'exists:teams,id',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'enabled' => [
                'boolean',
            ],
            'notifications' => [
                'array',
            ],
            'notifications.*' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    if (!(is_numeric($value) || filter_var($value, FILTER_VALIDATE_EMAIL))) {
                        $fail("The {$attribute} is invalid.");
                    }
                },
            ],
            'permissions' => [
                'array',
            ],
            'permissions.*'  => [
                'integer',
                'distinct',
                'exists:permissions,id',
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
