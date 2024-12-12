<?php

namespace App\Http\Requests\DataAccessApplication;

use App\Http\Requests\BaseFormRequest;

class CreateDataAccessApplication extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'applicant_id' => [
                'integer',
                'exists:users,id',
            ],
            'submission_status' => [
                'string',
                'in:DRAFT,SUBMITTED,FEEDBACK'
            ],
        ];
    }
}
