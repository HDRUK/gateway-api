<?php

namespace App\Http\Requests\V2\Tool;

use App\Http\Requests\BaseFormRequest;

class GetToolByTeamByIdByStatus extends BaseFormRequest
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
            'id' => 'required|int|exists:tools,id',
            'status' => 'required|string|in:active,draft,archived',
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
            'status' => $this->route('status'),
        ]);
    }
}
