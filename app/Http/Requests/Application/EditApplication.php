<?php

namespace App\Http\Requests\Application;

use App\Models\Application;
use App\Http\Requests\BaseFormRequest;

class EditApplication extends BaseFormRequest
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
                'string',
            ],
            'app_id' => [
                'string',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = Application::withTrashed()->where('app_id', $value)->where('id', '<>', $id)->count();

                    if ($exists) {
                        $fail('The selected app_id already exist.');
                    }
                },
            ],
            'client_id' => [
                'string',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = Application::withTrashed()->where('client_id', $value)->where('id', '<>', $id)->count();

                    if ($exists) {
                        $fail('The selected client_id already exist.');
                    }
                },
            ],
            'image_link' => [
                'string',
                'url',
            ],
            'description' => [
                'string',
            ],
            'team_id' => [
                'integer',
                'exists:teams,id',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'enabled' => [
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
