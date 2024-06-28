<?php

namespace App\Http\Requests\Library;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class CreateLibrary extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'int',
                'exists:users,id',
            ],
            'dataset_id' => [
                'int',
                'exists:datasets,id',
            ],
        ];
    }
}
