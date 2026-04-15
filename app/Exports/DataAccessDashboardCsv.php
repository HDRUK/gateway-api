<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;

class DataAccessDashboardCsv implements FromArray
{
    public function __construct(
        private $myApplications,
        private $averageTimeToApproval,
        private $requiredActions,
        private $applicationIimeline,
    ) {
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['My Applications'];
        foreach ($this->myApplications as $item) {
            $rows[] = [
                $item->submission_status,
                $item->counter,
            ];

        }

        $rows[] = [''];

        $rows[] = ['Current Status'];
        $rows[] = ['Application Title', 'Current Status', 'Last Activity'];
        foreach ($this->applicationIimeline as $item) {
            $latestStatus = end($item['states']);
            $rows[] = [
                $item['project_title'],
                $latestStatus['approval_status'] ?? $latestStatus['submission_status'],
                Carbon::parse($latestStatus['created_at'])->format('Y-m-d'),
            ];
        }

        $rows[] = [''];

        $rows[] = ['Application Timeline'];
        $rows[] = ['Application Title', 'Status', 'Days Between States'];
        foreach ($this->applicationIimeline as $item) {
            foreach ($item['states'] as $state) {
                $rows[] = [
                    $item['project_title'],
                    $state['approval_status'] ?? $state['submission_status'],
                    (string) $state['days_between_states'],
                ];
            }
        }

        $rows[] = [''];

        $rows[] = ['Average Time to Approval'];
        $rows[] = ['In Days'];
        $rows[] = [
            $this->averageTimeToApproval['avg_diff_days'],
        ];

        $rows[] = [''];

        $rows[] = ['Messages & Required Actions'];
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
