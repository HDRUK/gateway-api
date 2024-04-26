<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DataUseFullExport implements WithHeadings, FromCollection, WithMapping
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
            'Non-Gateway Datasets',
            'Non-Gateway Applicants',
            'Funders And Sponsors',
            'Other Approval Committees',
            'Gateway Outputs - Tools',
            'Gateway Outputs - Papers',
            'Non-Gateway Outputs',
            'Project Title',
            'Project ID',
            'Organisation Name',
            'Organisation Sector',
            'Lay Summary',
            'Technical Summary',
            'Latest Approval Date',
            'Manual Upload',
            'Rejection Reason',
            'Sublicence Arrangements',
            'Public Benefit Statement',
            'Data Sensitivity Level',
            'Project Start Date',
            'Project End Data',
            'Access Date',
            'Accredited Researcher Status',
            'Confidential Data Description',
            'Dataset Linkage Description',
            'Duty of Confidentiality',
            'Legal basis for Data Article 6',
            'Legal basis for Data Article 9',
            'National Data Opt-out',
            'Organisation ID',
            'Privacy Enhancements',
            'Request Category Type',
            'Request Frequency',
            'Access Type',
            'Enabled',
            'Last Activity',
            'Counter',
        ];
    }

    public function map($row): array
    {
        $array = [];
        $fieldNames = $this->fieldNames();

        foreach ($fieldNames as $name) {
            $array[] = [ $row[${$name}] ];
        }
       
        return $array;
    }

    public function exportable(array $data): array
    {
        $fieldNames = $this->fieldNames();
        $array = [];

        foreach ($data as $item) {
            foreach ($fieldNames as $name) {
                $array[] = [ ${$name} => $this->getValueFromPath($item, ${$name}) ];
            }
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

    private function fieldNames(): array
    {
        return [
            'nonGatewayDatasets',
            'nonGatewayApplicants',
            'fundersAndSponsors',
            'otherApprovalCommittees',
            'gatewayOutputsTools',
            'gatewayOutputsPapers',
            'nonGatewayOutputs',
            'projectTitle',
            'projectIdText',
            'organisationName',
            'organisationSector',
            'laySummary',
            'technicalSummary',
            'latestApprovalDate',
            'manualUpload',
            'rejectionReason',
            'sublicenceArrangements',
            'publicBenefitStatement',
            'dataSensitivityLevel',
            'projectStartDate',
            'projectEndDate',
            'accessDate',
            'accreditedResearcherStatus',
            'confidentialDataDescription',
            'datasetLinkageDescription',
            'dutyOfConfidentiality',
            'legalBasisForDataArticle6',
            'legalBasisForDataArticle9',
            'nationalDataOptout',
            'organisationId',
            'privacyEnhancements',
            'requestCategoryType',
            'requestFrequency',
            'accessType',
            'enabled',
            'lastActivity',
            'counter',
        ];
    }
}
