<?php

namespace App\Http\Requests\ProjectGrant;

use App\Http\Requests\BaseFormRequest;

class CreateProjectGrant extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'pid' => [
                'required',
                'string',
                'unique:project_grants,pid',
            ],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'team_id' => [
                'required',
                'integer',
                'exists:teams,id',
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
}

