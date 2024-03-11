<?php

namespace Database\Seeders;

use App\Models\Filter;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seed_dataset_filters();
        $this->seed_datauses_filters();
        $this->seed_tools_filters();
        $this->seed_collection_filters();
    }

    public function seed_dataset_filters(): void
    {
        $filters = [
            'publisherName',
            'containsTissue',
            'dataType',
            'collectionName',
            'dataUseTitles',
            'dateRange',
            'populationSize',
            'geographicLocation'
        ];

        $this->seed_filter("dataset",$filters);
    }

     public function seed_datauses_filters(): void
    {
        $filters = [
            'publisherName',
            'organisationName',
            'sector',
            'datasetTitles',
            'latestApprovalDate',
            'accessType'
        ];


        $this->seed_filter("dataUseRegister",$filters);
    }

    public function seed_tools_filters(): void
    {
        $filters = [
            'programmingLanguage',
            'category',
            'category_id',
            'license'
        ];

        $this->seed_filter("tool",$filters);
    }


    public function seed_collection_filters(): void
    {
        $filters = [
            'publisherName'
        ];

        $this->seed_filter("collection",$filters);
    }

    public function seed_filter(string $type, array $filters): void
    {
        foreach ($filters as $filter){
            $checkFilter = Filter::where([
                'type' => $type, 
                'keys' => $filter,
            ])->first();
            if (!$checkFilter) {
                Filter::create([
                    'type' => $type, 
                    'keys' => $filter,
                    'enabled' => true,
                ]);
            }
        }
    }

}
