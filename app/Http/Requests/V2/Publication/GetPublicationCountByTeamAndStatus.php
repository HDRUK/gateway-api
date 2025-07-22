<?php

namespace App\Http\Requests\V2\Publication;

use App\Http\Requests\BaseFormRequest;

class GetPublicationCountByTeamAndStatus extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'teamId' => 'required|int|exists:teams,id',
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'teamId' => $this->route('teamId'),
        ]);
    }
}
