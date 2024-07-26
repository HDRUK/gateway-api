<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DataUseExport implements WithHeadings, FromCollection, WithMapping
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
            'Project Title',
            'Organisation Name',
            'Dataset Titles',
            'Publisher',
        ];
    }

    public function map($row): array
    {
        return [
            $row['projectTitle'],
            $row['organisationName'],
            $row['datasetTitles'],
            $row['publisher'],
        ];
    }

    public function exportable(array $data): array
    {
        // Project Title		'_source/projectTitle'
        // Organisation Name	'organisationName'
        // Dataset Title		'_source/datasetTitles'
        // Publisher		'team/name'

        $array = [];
        foreach ($data as $item) {
            $projectTitle = $this->getValueFromPath($item, '_source/projectTitle');
            $organisationName = $this->getValueFromPath($item, 'organisationName');
            $datasetTitles = $this->getValueFromPath($item, '_source/datasetTitles');
            $publisher = $this->getValueFromPath($item, 'team/name');
            
            $array[] = [
                'projectTitle' => $projectTitle,
                'organisationName' => $organisationName,
                'datasetTitles' => !is_array($datasetTitles) ?
                    $datasetTitles : (count($datasetTitles) ? implode(', ', $datasetTitles) : ''),
                'publisher' => $publisher,
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
