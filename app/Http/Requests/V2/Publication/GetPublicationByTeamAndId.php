<?php

namespace App\Http\Requests\V2\Publication;

use App\Http\Requests\BaseFormRequest;

class GetPublicationByTeamAndId extends BaseFormRequest
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
            'id' => 'required|int|exists:publications,id',
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
            'id' => $this->route('id'),
        ]);
    }
}
