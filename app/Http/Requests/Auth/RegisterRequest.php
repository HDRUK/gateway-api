<?php

namespace App\Http\Requests\Auth;

use Config;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $provider = $this->input('provider') === Config::get('constants.provider.cruk')
            ? Config::get('constants.provider.cruk')
            : Config::get('constants.provider.service');

        return [
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users')->where(fn ($query) => $query->where('provider', $provider)),
            ],
            'password' => ['required', 'string', 'min:8'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string'],
        ];
    }
}
