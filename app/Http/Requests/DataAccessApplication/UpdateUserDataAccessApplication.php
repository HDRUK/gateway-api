<?php

namespace App\Http\Requests\DataAccessApplication;

use App\Http\Requests\BaseFormRequest;

class UpdateUserDataAccessApplication extends BaseFormRequest
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
                'exists:dar_applications,id',
            ],
            'userId' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'applicant_id' => [
                'integer',
                'required',
                'exists:users,id',
            ],
            'submission_status' => [
                'string',
                'required',
                'in:DRAFT,SUBMITTED,FEEDBACK',
            ],
            'project_title' => [
                'string',
                'required',
            ],
            'approval_status' => [
                'string',
                'in:WITHDRAWN',
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
        $this->merge([
            'id' => $this->route('id'),
            'userId' => $this->route('userId'),
        ]);
    }
}
