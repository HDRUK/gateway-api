<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DatasetStructuralMetadataExport implements WithHeadings, FromCollection, WithMapping
{
    use Exportable;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $this->exportable($data);
    }
    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        // Define the headings for your Excel file
        return [
            'Table Name',
            'Table Description',
            'Column Name',
            'Column Description',
            'Data Type',
            'Sensitivity',
        ];
    }

    public function map($row): array
    {
        return [
            $row['table_name'],
            $row['table_description'],
            $row['column_name'],
            $row['column_description'],
            $row['data_type'],
            $row['sensitivity'],
        ];
    }

    public function exportable(array $data): array
    {
        $return = [];

        foreach ($data as $item) {
            foreach ($item['columns'] as $column) {
                $return[] = [
                    'table_name' => $item['name'] ? $item['name'] : '',
                    'table_description' => $item['description'] ? $item['description'] : '',
                    'column_name' => $column['name'] ? $column['name'] : '',
                    'column_description' => $column['description'] ? $column['description'] : '',
                    'data_type' => $column['dataType'] ? $column['dataType'] : '',
                    'sensitivity' => $column['sensitive'] === true ? 'true' : 'false',    
                ];
            }
        }

        return $return;
    }
}
