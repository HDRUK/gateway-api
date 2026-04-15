<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;

class DataAccessRequiredActionsCsv implements FromArray
{
    public function __construct(
        private $requiredActions,
    ) {
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['messages & required actions'];
        $rows[] = ['Last Activity', 'Application Title', 'Application User', 'Data Custodian'];
        foreach ($this->requiredActions as $item) {
            $lastActivity = Carbon::parse($item['created_at'])->format('Y-m-d');
            $appTitle = $item['project_title'];
            $appUser = $item['user']['name'];
            $dataCustodian = $item['team']['name'];

            $rows[] = [
                $lastActivity,
                $appTitle,
                $appUser,
                $dataCustodian,
            ];
        }

        return $rows;
    }
}
