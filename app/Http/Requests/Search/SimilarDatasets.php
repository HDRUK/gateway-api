<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\BaseFormRequest;

class SimilarDatasets extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
        ];
    }
}
