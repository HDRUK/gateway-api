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
            'app_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = Application::withTrashed()->where('app_id', $value)->where('id', '<>', $id)->count();

                    if ($exists) {
                        $fail('The selected app_id already exist.');
                    }
                },
            ],
            'client_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = Application::withTrashed()->where('client_id', $value)->where('id', '<>', $id)->count();

                    if ($exists) {
                        $fail('The selected client_id already exist.');
                    }
                },
            ],
            'image_link' => [
                'required',
                'string',
                'url',
            ],
            'description' => [
                'required',
                'string',
            ],
            'team_id' => [
                'required',
                'exists:teams,id',
            ],
            'user_id' => [
                'required',
                'exists:users,id',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'tags' => [
                'present',
                'array',
            ],
            'tags.*'  => [
                'required',
                'integer',
                'distinct',
                'exists:tags,id',
            ],
            'permissions' => [
                'present',
                'array',
            ],
            'permissions.*'  => [
                'required',
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
