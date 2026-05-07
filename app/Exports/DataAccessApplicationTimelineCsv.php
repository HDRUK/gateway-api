<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;

class DataAccessApplicationTimelineCsv implements FromArray
{
    public function __construct(
        private $applicationIimeline,
    ) {
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['Application Timeline'];
        $rows[] = ['Application Title', 'Current Status', 'Intermediar State', 'Days Between States', 'Last Activity'];
        foreach ($this->applicationIimeline as $item) {
            $appTitle = $item['project_title'];
            $latestActivity = $item['created_at'];
            $latestStatus = end($item['states']);

            foreach ($item['states'] as $value) {
                $rows[] = [
                    $appTitle,
                    $latestStatus['approval_status'] ?? $latestStatus['submission_status'],
                    $value['approval_status'] ?? $value['submission_status'],
                    $value['days_between_states'],
                    Carbon::parse($latestActivity)->format('Y-m-d'),
                ];
            }
        }

        return $rows;
    }
}
