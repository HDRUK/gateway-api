<?php

namespace App\Http\Requests\CohortRequest;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

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
                Rule::in(['APPROVED','REJECTED','BANNED','SUSPENDED']),
            ],
            'nhse_sde_request_status' => [
                'string',
                'nullable',
                Rule::in([null, 'IN PROCESS', 'APPROVAL REQUESTED', 'APPROVED','REJECTED','BANNED','SUSPENDED']),
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
