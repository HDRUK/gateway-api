<?php

namespace App\Http\Requests\DataAccessTemplate;

use App\Http\Requests\BaseFormRequest;

class EditDataAccessTemplate extends BaseFormRequest
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
                'exists:dar_templates,id',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'team_id' => [
                'integer',
                'exists:users,id',
            ],
            'published' => [
                'boolean',
            ],
            'locked' => [
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
