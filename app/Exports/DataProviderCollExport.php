<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DataProviderCollExport implements WithHeadings, FromCollection, WithMapping
{
    use Exportable;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $this->exportable($data);
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        // Define the headings for your Excel file
        return [
            'Data Provider Name',
            'Dataset Titles',
            'Geographic Locations',
        ];
    }

    public function map($row): array
    {
        return [
            $row['name'],
            $row['datasetTitles'],
            $row['geographicLocations'],
        ];
    }

    public function exportable(array $data): array
    {
        $array = [];
        foreach ($data as $item) {
            $name = $this->getValueFromPath($item, 'name');
            $datasetTitles = $this->getValueFromPath($item, 'datasetTitles');
            $geographicLocations = $this->getValueFromPath($item, 'geographicLocations');
            
            $array[] = [
                'name' => $name,
                'datasetTitles' => !is_array($datasetTitles) ? $datasetTitles : (count($datasetTitles) ? implode(', ', $datasetTitles) : ''),
                'geographicLocations' => !is_array($geographicLocations) ? $geographicLocations : (count($geographicLocations) ? implode(', ', $geographicLocations) : ''),
            ];
        }

        return $array;
    }

    public function getValueFromPath(array $item, string $path) 
    {
        $keys = explode('/', $path);

        $return = $item;
        foreach ($keys as $key) {
            if (isset($return[$key])) {
                $return = $return[$key];
            } else {
                return null;
            }
        }
        
        return $return;
    }
}
