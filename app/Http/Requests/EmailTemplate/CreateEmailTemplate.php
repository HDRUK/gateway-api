<?php

namespace App\Http\Requests\EmailTemplate;

use App\Http\Requests\BaseFormRequest;
use App\Models\EmailTemplate;

class CreateEmailTemplate extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $emailTemplate = EmailTemplate::where('identifier', $value)
                        ->where('enabled', $this->input('enabled'))
                        ->first();

                    if ($emailTemplate) {
                        $fail('The selected identifier is invalid, already exist.');
                    }
                }
            ],
            'subject' => 'required|string',
            'body' => 'required|string',
            'enabled' => 'required|boolean',
        ];
    }
}
