<?php

namespace App\Http\Requests\EmailTemplate;

use App\Models\EmailTemplate;
use App\Http\Requests\BaseFormRequest;

class EditEmailTemplate extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'int',
                'exists:email_templates,id',
            ],
            'identifier' => [
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $emailTemplate = EmailTemplate::where('identifier', $value)
                        ->where('id', '<>', $this->id)
                        ->first();

                    if ($emailTemplate) {
                        $fail('The selected identifier is invalid, already exist.');
                    }
                }
            ],
            'subject' => 'string',
            'body' => 'string',
            'enabled' => 'boolean',
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
