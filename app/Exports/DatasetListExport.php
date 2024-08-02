<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DatasetListExport implements WithHeadings, FromCollection, WithMapping
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
            'Metadata title',
            'Metadata abstract',
            'Population',
            'Data range',
            'Access Service',
            'Data standard',
            'Data provider',
            'Dataset type',
        ];
    }

    public function map($row): array
    {
        return [
            $row['title'],
            $row['abstract'],
            $row['populationSize'],
            $row['dataRange'],
            $row['accessService'],
            $row['dataStandard'],
            $row['publisher'],
            $row['datasetType'],
        ];
    }

    public function exportable(array $data): array
    {
        $array = [];
        foreach ($data as $item) {
            $version = $this->getValueFromPath($item, 'metadata/gwdmVersion');
            $title = $this->getValueFromPath($item, 'metadata/metadata/summary/title');
            $abstract = $this->getValueFromPath($item, 'metadata/metadata/summary/abstract');
            $populationSize = ($version !== '1.0') ?
                $this->getValueFromPath($item, 'metadata/metadata/summary/populationSize') : '';
            $startDate = $this->getValueFromPath($item, 'metadata/metadata/provenance/temporal/startDate');
            $endData = $this->getValueFromPath($item, 'metadata/metadata/provenance/temporal/endData');
            $accessService = $this->getValueFromPath($item, 'metadata/metadata/accesibility/access/accessService');
            $dataStandard = $this->getValueFromPath($item, 'metadata/metadata/accesibility/formatAndStandards/conformsTo');
            $publisher = '';
            if ($version === '1.0') {
                $publisher = $this->getValueFromPath($item, 'metadata/metadata/summary/publisher/publisherName');
            } else {
                $publisher = $this->getValueFromPath($item, 'metadata/metadata/summary/publisher/name');
            }
            $datasetType = $this->getValueFromPath($item, 'metadata/metadata/summary/datasetType');
            
            $array[] = [
                'title' => $title,
                'abstract' => $abstract,
                'populationSize' => (int) $populationSize,
                'dataRange' => $this->convertDate($startDate, $endData),
                'accessService' => $accessService,
                'dataStandard' => $dataStandard,
                'publisher' => $publisher,
                'datasetType' => $datasetType,
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

    public function convertDate($start, $stop)
    {
        if (!$start && !$stop) {
            return '';
        }

        if ($start && !$stop) {
            $splitDate = explode('-', $start);
            return $splitDate[0];
        }
        
        if (!$start && $stop) {
            $splitDate = explode('-', $stop);
            return $splitDate[0];
        }

        if ($start && $stop) {
            $splitStartDate = explode('-', $start);
            $splitEndDate = explode('-', $stop);
            return $splitStartDate[0] . " - " . $splitEndDate[0];
        }

    }
}
