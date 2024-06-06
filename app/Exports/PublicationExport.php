<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class PublicationExport implements WithHeadings, FromCollection, WithMapping
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
            'Title',
            'Authors',
            'Journal Title',
            'Abstract Text',
            'Date of Publication',
        ];
    }

    public function map($row): array
    {
        return [
            $row['title'],
            $row['authors'],
            $row['journalTitle'],
            $row['abstractText'],
            $row['dateOfPublication']
        ];
    }

    public function exportable(array $data): array
    {
        // Title		        'paper_title'
        // Authors	            'authors'
        // Journal Title		'journal_name'
        // Abstract Text		'abstract'
        // Date of Publication  'year_of_publication'

        $array = [];
        foreach ($data as $item) {
            $title = $this->getValueFromPath($item, 'paper_title');
            $authors = $this->getValueFromPath($item, 'authors');
            $journalTitle = $this->getValueFromPath($item, 'journal_name');
            $abstractText = $this->getValueFromPath($item, 'abstract');
            $dateOfPublication = $this->getValueFromPath($item, 'year_of_publication');
            
            $array[] = [
                'title' => $title,
                'authors' => $authors,
                'journalTitle' => $journalTitle,
                'abstractText' => $abstractText,
                'dateOfPublication' => $dateOfPublication,
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
