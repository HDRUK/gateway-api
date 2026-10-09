<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;

class RevokeUserSessions extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'userId' => [
                'int',
                'required',
                'exists:users,id',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([ 'userId' => $this->route('userId') ]);
    }
}
