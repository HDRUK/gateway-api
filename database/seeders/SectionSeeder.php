<?php

namespace Database\Seeders;

use App\Models\DarSection;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = [
            [
                'name' => 'Before you Begin',
                'sub_section' => null,
                'order' => 0,
            ],
            [
                'name' => 'Safe People',
                'sub_section' => null,
                'order' => 1,
            ],
            [
                'name' => 'Primary Applicant',
                'sub_section' => 'Safe People',
                'order' => 1,
            ],
            [
                'name' => 'Other Individuals',
                'sub_section' => 'Safe People',
                'order' => 1,
            ],
            [
                'name' => 'Safe Project',
                'sub_section' => null,
                'order' => 2,
            ],
            [
                'name' => 'About this application',
                'sub_section' => 'Safe Project',
                'order' => 2,
            ],
            [
                'name' => 'Project Details',
                'sub_section' => 'Safe Project',
                'order' => 2,
            ],
            [
                'name' => 'Further Information',
                'sub_section' => 'Safe Project',
                'order' => 2,
            ],
            [
                'name' => 'Sponsor Information',
                'sub_section' => 'Safe Project',
                'order' => 2,
            ],
            [
                'name' => 'Declaration of interest',
                'sub_section' => 'Safe Project',
                'order' => 2,
            ],
            [
                'name' => 'Intellectual Property',
                'sub_section' => 'Safe Project',
                'order' => 2,
            ],
            [
                'name' => 'Safe Data',
                'sub_section' => null,
                'order' => 3,
            ],
            [
                'name' => 'Data Fields',
                'sub_section' => 'Safe Data',
                'order' => 3,
            ],
            [
                'name' => 'Analysis',
                'sub_section' => 'Safe Data',
                'order' => 3,
            ],
            [
                'name' => 'Other Datasets (intention to link data)',
                'sub_section' => 'Safe Data',
                'order' => 3,
            ],
            [
                'name' => 'Lawful Basis',
                'sub_section' => 'Safe Data',
                'order' => 3,
            ],
            [
                'name' => 'Confidentiality Avenue',
                'sub_section' => 'Safe Data',
                'order' => 3,
            ],
            [
                'name' => 'Ethic Approval',
                'sub_section' => 'Safe Data',
                'order' => 3,
            ],
            [
                'name' => 'Safe Settings',
                'sub_section' => null,
                'order' => 4,
            ],
            [
                'name' => 'Storage & Processing',
                'sub_section' => 'Safe Settings',
                'order' => 4,
            ],
            [
                'name' => 'Data Flow',
                'sub_section' => 'Safe Settings',
                'order' => 4,
            ],
            [
                'name' => 'Safe Outputs',
                'sub_section' => null,
                'order' => 5,
            ],
            [
                'name' => 'Sharing',
                'sub_section' => 'Safe Outputs',
                'order' => 5,
            ],
            [
                'name' => 'Retention',
                'sub_section' => 'Safe Outputs',
                'order' => 5,
            ],
            [
                'name' => 'Destruction',
                'sub_section' => 'Safe Outputs',
                'order' => 5,
            ],
            [
                'name' => 'Additional Information & Files',
                'sub_section' => null,
                'order' => 6,
            ],
            [
                'name' => 'Files',
                'sub_section' => 'Additional Information & Files',
                'order' => 6,
            ],
        ];

        foreach ($sections as $s) {
            DarSection::create([
                'name' => $s['name'],
                'sub_section' => $s['sub_section'],
                'order' => $s['order'],
            ]);
        }
    }
}
