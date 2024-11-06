<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DatasetTableExport implements WithHeadings, FromCollection, WithMapping
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
            'Metadata Title',
            'Population',
            'Date range',
            'Access Service',
            'Data standard',
            'Cohort Discovery',
            'Structural Metadata',
            'Data provider',
        ];
    }

    public function map($row): array
    {
        return [
            $row['title'],
            $row['populationSize'],
            $row['dateRange'],
            $row['accessService'],
            $row['dataStandard'],
            $row['cohortDiscovery'],
            $row['structuralMetadata'],
            $row['publisher'],
        ];
    }

    public function exportable(array $data): array
    {
        $array = [];
        foreach ($data as $item) {
            $version = $this->getValueFromPath($item, 'gwdmVersion');
            $title = $this->getValueFromPath($item, 'metadata/summary/title');
            $populationSize = ($version !== '1.0') ?
                $this->getValueFromPath($item, 'metadata/summary/populationSize') : '';
            $startDate = $this->getValueFromPath($item, 'metadata/provenance/temporal/startDate');
            $endDate = $this->getValueFromPath($item, 'metadata/provenance/temporal/endDate');
            $accessService = $this->getValueFromPath($item, 'metadata/accessibility/access/accessService');
            $dataStandard = $this->getValueFromPath($item, 'metadata/accessibility/formatAndStandards/conformsTo');
            $cohortDiscovery = $this->getValueFromPath($item, 'isCohortDiscovery');
            $structuralMetadata = $this->getValueFromPath($item, 'metadata/structuralMetadata');
            $publisher = '';
            if ($version === '1.0') {
                $publisher = $this->getValueFromPath($item, 'metadata/summary/publisher/publisherName');
            } else {
                $publisher = $this->getValueFromPath($item, 'metadata/summary/publisher/name');
            }

            $array[] = [
                'title' => $title,
                'populationSize' => (int)$populationSize,
                'dateRange' => $this->convertDate($startDate, $endDate),
                'accessService' => $accessService,
                'dataStandard' => $dataStandard,
                'cohortDiscovery' => $cohortDiscovery,
                'structuralMetadata' => ($structuralMetadata === null) ?
                    '' : (count($structuralMetadata) ? 'true' : ''),
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
            $splitEndDate = explode('-', $start);
            return $splitStartDate[0] . " - " . $splitEndDate[0];
        }

    }
}
