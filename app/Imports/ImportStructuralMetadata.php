<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportStructuralMetadata implements ToArray, WithHeadingRow
{
    public function array(array $row)
    {
        return $row;
    }
}
