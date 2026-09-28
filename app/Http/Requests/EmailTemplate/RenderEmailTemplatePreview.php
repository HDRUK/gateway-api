<?php

namespace App\Http\Requests\EmailTemplate;

use App\Http\Requests\BaseFormRequest;

class RenderEmailTemplatePreview extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'body' => 'required|string',
        ];
    }
}
