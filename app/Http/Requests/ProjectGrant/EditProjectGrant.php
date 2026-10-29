<?php

namespace App\Http\Requests\ProjectGrant;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class EditProjectGrant extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:project_grants,id',
            ],
            'pid' => [
                'sometimes',
                'string',
                Rule::unique('project_grants', 'pid')->ignore($this->route('id')),
            ],
            'projectGrantName' => [
                'sometimes',
                'string',
            ],
            'leadResearcher' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'leadResearchInstitute' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'grantNumbers' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'grantNumbers.*' => [
                'string',
            ],
            'projectGrantStartDate' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'projectGrantEndDate' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'projectGrantScope' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'datasets' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'datasets.*' => [
                'integer',
                'exists:datasets,id',
            ],
            'publications' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'publications.*' => [
                'integer',
                'exists:publications,id',
            ],
            'tools' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'tools.*' => [
                'integer',
                'exists:tools,id',
            ],
            'with_related' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
