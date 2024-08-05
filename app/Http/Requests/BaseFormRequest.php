<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $response = [
            'status' => 'INVALID_ARGUMENT',
            'message' => 'Invalid argument(s)',
            'errors' => $this->formatErrors($validator),
        ];

        throw new HttpResponseException(response()->json($response, 400));
    }

    /**
     * Format the validation errors.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        $errors = [];

        foreach ($validator->errors()->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $rules = $validator->failed()[$field];

                foreach ($rules as $rule => $ruleData) {
                    $errors[] = [
                        'reason' => strtoupper($rule),
                        'message' => $message,
                        'field' => $field,
                    ];
                }
            }
        }

        return $errors;
    }
}
