<?php

namespace App\Http\Requests\CohortRequest;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateCohortRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'int',
                'required',
                'exists:cohort_requests,id',
            ],
            'details' => [
                'required',
                'string',
            ],
            'request_status' => [
                'string',
                'required',
                Rule::in(['PENDING', 'APPROVED', 'REJECTED', 'BANNED', 'SUSPENDED']),
            ],
            'nhse_sde_request_status' => [
                'string',
                'nullable',
                Rule::in([null, 'IN PROCESS', 'APPROVAL REQUESTED', 'APPROVED', 'REJECTED', 'BANNED', 'SUSPENDED']),
            ],
            'workgroup_ids' => [
                'nullable',
                'array',
            ],
            'workgroup_ids.*' => [
                'integer',
                'exists:workgroups,id',
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
