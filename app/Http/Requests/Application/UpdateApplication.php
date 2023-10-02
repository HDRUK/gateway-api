<?php

namespace App\Http\Requests\Application;

use App\Http\Requests\BaseFormRequest;
use App\Models\Application;

class UpdateApplication extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');
        
        return [
            'id' => [
                'required',
                'exists:applications,id',
            ],
            'name' => [
                'required',
                'string',
            ],
            'image_link' => [
                'string',
                'nullable',
                'url',
            ],
            'description' => [
                'required',
                'string',
            ],
            'team_id' => [
                'required',
                'integer',
                'exists:teams,id',
            ],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'tags' => [
                'array',
            ],
            'tags.*'  => [
                'integer',
                'distinct',
                'exists:tags,id',
            ],
            'permissions' => [
                'array',
            ],
            'permissions.*'  => [
                'integer',
                'distinct',
                'exists:permissions,id',
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
