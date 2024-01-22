<?php

namespace Database\Seeders;

use App\Models\Keyword;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keywords = [
            'A&e',
            'Anxiety',
            'Biobank',
            'Bipolar Disorder',
            'Bladder Cancer',
            'Bowel Cancer',
            'Breast Cancer',
            'British Heart Foundation Data Science Centre',
            'Cancer',
            'Cervical Cancer',
            'Charity',
            'Cognitive',
            'Cohort Discovery',
            'Colorectal Cancer',
            'Covid-19',
            'Critical Care',
            'Cystic Fibrosis Trust',
            'Data Science',
            'Datamind',
            'Dataset',
            'Depression',
            'Emergency Care',
            'Gateway',
            'Genetics',
            'Hdruk',
            'Healthcare Quality Improvemnet Partnership',
            'Leukaemia',
            'Longitudinal',
            'Lymphoma',
            'Maternal Health',
            'Mental Health',
            'National Core Study',
            'Neonatal',
            'Nnrd',
            'Outpatient',
            'Ovarian Cancer',
            'Pancreatic Cancer',
            'Parkinson\'s Uk',
            'Pharmacy',
            'Physical Measures',
            'Pregnancy',
            'Prescribing',
            'Primary Care',
            'Prostate Cancer',
            'Psychiatry',
            'Psychology',
            'Renal Cancer',
            'Research Data Scotland',
            'Royal College Of General Practitioners',
            'Schizophrenia',
            'Surgery',
            'The Brain Tumour Charity',
            'The Renal Association',
            'Wellbeing',
        ];

        $dataToInsert = [];
        foreach ($keywords as $keyword) {
            $dataToInsert[] = ['name' => htmlspecialchars($keyword), 'enabled' => fake()->randomElement([0, 1])];
        }

        Keyword::insert($dataToInsert);
    }
}
