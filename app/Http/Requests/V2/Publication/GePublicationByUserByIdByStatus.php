<?php

namespace App\Http\Requests\V2\Publication;

use App\Http\Requests\BaseFormRequest;

class GePublicationByUserByIdByStatus extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'userId' => 'required|int|exists:users,id',
            'id' => 'required|int|exists:publications,id',
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
            'userId' => $this->route('userId'),
            'id' => $this->route('id'),
            'status' => $this->route('status'),
        ]);
    }
}
