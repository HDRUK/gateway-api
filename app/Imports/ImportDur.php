<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportDur implements WithMultipleSheets
{
    private $data;
    public $durImport;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        if (!$this->durImport) {
            $this->durImport = new DataUsesTemplateImport($this->data);
        }

        return [
            'Data Uses Template' => $this->durImport
        ];
    }
}
