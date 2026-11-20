<?php

namespace App\Http\Requests\V3\Dataset;

use App\Http\Requests\V2\Dataset\UpdateDataset as V2UpdateDataset;

class UpdateDataset extends V2UpdateDataset
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => [
                'required',
                'string',
                'in:ACTIVE,ARCHIVED,DRAFT',
            ],
        ];
    }
}
