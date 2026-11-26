<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Http\Requests\BaseFormRequest;

class UpdateUser extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        // OpenAthens SSO emails are owned by the login flow, not the profile
        // form: the controller ignores the submitted value, so don't reject
        // the save over a legacy invalid address the client echoes back (GAT-9881).
        if (User::where('id', $id)->value('provider') === 'open-athens') {
            $emailRules = ['nullable'];
        }

        return [
            'id' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'firstname' => [
                'required',
                'string',
            ],
            'lastname' => [
                'required',
                'string',
            ],
            'email' => $emailRules ?? [
                'required',
                'string',
                'email',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = User::withTrashed()->where('email', $value)->where('id', '<>', $id)->count();

                    if ($exists) {
                        $fail('The selected email already exist.');
                    }
                },
            ],
            'secondary_email' => [
                'nullable',
                'string',
                'email',
            ],
            'preferred_email' => [
                'required',
                'string',
                'in:primary,secondary',
            ],
            'password' => [
                'nullable',
                'string',
            ],
            'sector_id' => [
                'required',
                'integer',
                'exists:sectors,id',
            ],
            'organisation' => [
                'nullable',
                'string',
            ],
            'provider' => [
                'nullable',
                'string',
            ],
            'providerid' => [
                'nullable',
                'string',
            ],
            'bio' => [
                'nullable',
                'string',
            ],
            'domain' => [
                'nullable',
                'string',
            ],
            'link' => [
                'nullable',
                'string',
            ],
            'orcid' => [
                'nullable',
                'string',
            ],
            'contact_feedback' => [
                'required',
                'boolean',
            ],
            'contact_news' => [
                'required',
                'boolean',
            ],
            'terms' => [
                'boolean',
            ],
            'is_nhse_sde_approval' => [
                'boolean',
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
