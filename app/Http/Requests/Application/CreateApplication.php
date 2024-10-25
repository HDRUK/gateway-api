<?php

namespace App\Http\Requests\Application;

use Closure;
use App\Http\Requests\BaseFormRequest;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

class CreateApplication extends BaseFormRequest
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
                'required',
                'string',
            ],
            'image_link' => [
                'string',
                'nullable',
                'url',
            ],
            'description' => [
                'required',
                'string',
            ],
            'team_id' => [
                'required',
                'integer',
                'exists:teams,id',
            ],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'enabled' => [
                'required',
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
}
