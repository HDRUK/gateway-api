<?php

namespace App\Http\Requests\Application;

use Closure;
use App\Http\Requests\BaseFormRequest;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

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
                    $validator = new EmailValidator();
                    $is_email = $validator->isValid($value, new RFCValidation());
                    if (!(is_numeric($value) || $is_email)) {
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
