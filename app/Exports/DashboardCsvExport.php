<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class DashboardCsvExport implements FromArray
{
    public function __construct(
        private $entityDatasets,
        private $entityDataUses,
        private $entityTools,
        private $entityPublications,
        private $entityCollections,
        private $entityGeneralEnquiries,
        private $entityFeasabilityEnquiries,
        private $entityDataAccessRequests,
        private $dataset360Views,
        private $datasetTopViews,
        private $collectionViews,
        private $dataCustodianViews,
        private $startDate,
        private $endDate,
    ) {
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = [$this->startDate . ' - ' . $this->endDate];
        $rows[] = ['Your resources'];

        // datasets
        $rows[] = ['Datasets'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityDatasets['total']),
            $this->val($this->entityDatasets['total_by_interval']),
        ];

        $rows[] = [''];

        // data uses
        $rows[] = ['Data Uses'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityDataUses['total']),
            $this->val($this->entityDataUses['total_by_interval']),
        ];

        $rows[] = [''];

        // tools
        $rows[] = ['Analysis Scripts'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityTools['total']),
            $this->val($this->entityTools['total_by_interval']),
        ];

        $rows[] = [''];

        // publications
        $rows[] = ['Publications'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityPublications['total']),
            $this->val($this->entityPublications['total_by_interval']),
        ];

        $rows[] = [''];

        // collections
        $rows[] = ['Collections'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityCollections['total']),
            $this->val($this->entityCollections['total_by_interval']),
        ];

        $rows[] = [''];

        // 360 dataset views
        $rows[] = ['360 Dataset Views'];
        $rows[] = ['Date', 'Counter'];
        foreach ($this->dataset360Views as $item) {
            $rows[] = [
                $this->val($item['date']),
                $this->val($item['counter']),
            ];
        }

        $rows[] = [''];

        // top dataset views
        $rows[] = ['Most Dataset Views'];
        $rows[] = ['Title', 'Counter'];
        foreach ($this->datasetTopViews as $item) {
            $rows[] = [
                $this->val($item['title']),
                $this->val($item['counter']),
            ];
        }

        $rows[] = [''];

        // other views
        $rows[] = ['Other Views'];

        // collections
        $rows[] = ['Collections'];
        $rows[] = ['Counter'];
        $rows[] = [$this->val($this->collectionViews['counter'])];

        $rows[] = [''];

        // data custodian page
        $rows[] = ['Data Custodian page'];
        $rows[] = ['Counter'];
        $rows[] = [$this->val($this->dataCustodianViews['counter'])];

        $rows[] = [''];

        // enquiries and requests
        $rows[] = ['Enquiries and requests'];

        // general enquiries
        $rows[] = ['General enquiries'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityGeneralEnquiries['total']),
            $this->val($this->entityGeneralEnquiries['total_by_interval']),
        ];

        $rows[] = [''];

        // feasibility enquiries
        $rows[] = ['Feasibility enquiries'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityFeasabilityEnquiries['total']),
            $this->val($this->entityFeasabilityEnquiries['total_by_interval']),
        ];

        $rows[] = [''];

        // data access requests
        $rows[] = ['Data access requests'];
        $rows[] = ['Total', 'Total By Interval'];
        $rows[] = [
            $this->val($this->entityDataAccessRequests['total']),
            $this->val($this->entityDataAccessRequests['total_by_interval']),
        ];

        return $rows;
    }

    private function val(mixed $value): string
    {
        return (string) ($value ?? 0);
    }
}
