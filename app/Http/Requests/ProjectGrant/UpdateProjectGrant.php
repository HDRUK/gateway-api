<?php

namespace App\Http\Requests\ProjectGrant;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class UpdateProjectGrant extends BaseFormRequest
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
            'version' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'projectGrantName' => [
                'required',
                'string',
            ],
            'leadResearcher' => [
                'nullable',
                'string',
            ],
            'leadResearchInstitute' => [
                'nullable',
                'string',
            ],
            'grantNumbers' => [
                'nullable',
                'array',
            ],
            'grantNumbers.*' => [
                'string',
            ],
            'projectGrantStartDate' => [
                'nullable',
                'date',
            ],
            'projectGrantEndDate' => [
                'nullable',
                'date',
            ],
            'projectGrantScope' => [
                'nullable',
                'string',
            ],
            'datasets' => [
                'nullable',
                'array',
            ],
            'datasets.*' => [
                'integer',
                'exists:datasets,id',
            ],
            'publications' => [
                'nullable',
                'array',
            ],
            'publications.*' => [
                'integer',
                'exists:publications,id',
            ],
            'tools' => [
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
