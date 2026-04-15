<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class DataAccessApplicationTimelineCsv implements FromArray
{
    public function __construct(
        private $applicationIimeline,
    ) {
    }

    public function array(): array
    {
        $rows = [];

        return $rows;
    }
}
