<?php

namespace App\Http\Requests\Dataset;

use App\Http\Requests\BaseFormRequest;

class CompareDatasets extends BaseFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $max = (int) config('comparison.max_datasets', 4);

        return [
            'dataset_ids' => ['required', 'array', 'min:2', "max:{$max}"],
            'dataset_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }
}
