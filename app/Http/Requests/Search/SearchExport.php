<?php

namespace App\Http\Requests\Search;

class SearchExport extends Search
{
    protected function prepareForValidation(): void
    {
        $this->merge(['type' => $this->route('type')]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'download_type' => ['nullable', 'in:list,table'],
        ]);
    }
}
