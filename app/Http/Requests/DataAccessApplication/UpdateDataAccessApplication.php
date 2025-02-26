<?php

namespace App\Http\Requests\DataAccessApplication;

use App\Http\Requests\BaseFormRequest;

class UpdateDataAccessApplication extends BaseFormRequest
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
            'teamId' => [
                'int',
                'required',
                'exists:teams,id',
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
                'in:APPROVED,APPROVED_COMMENTS,REJECTED',
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
            'teamId' => $this->route('teamId'),
        ]);
    }
}
