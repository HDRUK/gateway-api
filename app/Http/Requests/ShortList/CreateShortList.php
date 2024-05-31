<?php

namespace App\Http\Requests\ShortList;

use Illuminate\Validation\Rule;
use App\Http\Requests\BaseFormRequest;

class CreateShortList extends BaseFormRequest
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
