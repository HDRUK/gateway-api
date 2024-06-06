<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ToolListExport implements WithHeadings, FromCollection, WithMapping
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
            'Tool Name',
            'Tool Category',
            'Programming language',
            'Description',
        ];
    }

    public function map($row): array
    {
        return [
            $row['name'],
            $row['category'],
            $row['programmingLanguage'],
            $row['description'],
        ];
    }

    public function exportable(array $data): array
    {
        $response = [];

        foreach ($data as $item) {
            $name = array_key_exists('name', $item['_source']) ? $item['_source']['name'] : '';
            $category = array_key_exists('category', $item['_source']) ? $item['_source']['category'] : '';
            $programmingLanguage = array_key_exists('programmingLanguage', $item['_source']) ? $item['_source']['programmingLanguage'] : '';
            $description = array_key_exists('description', $item['_source']) ? $item['_source']['description'] : '';

            $response[] = [
                'name' => $name,
                'category' => $category,
                'programmingLanguage' => $programmingLanguage,
                'description' => $description,
            ];
        }

        return $response;
    }
}
