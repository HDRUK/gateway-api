<?php

namespace App\Http\Requests\DataAccessApplication;

use App\Http\Requests\BaseFormRequest;

class EditDataAccessApplication extends BaseFormRequest
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
                'exists:users,id',
            ],
            'submission_status' => [
                'string',
                'in:DRAFT,SUBMITTED',
            ],
            'project_title' => [
                'string',
            ],
            'approval_status' => [
                'string',
                'nullable',
                'in:APPROVED,APPROVED_COMMENTS,FEEDBACK,REJECTED',
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
