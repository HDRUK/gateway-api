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
    }

    public function seed_dataset_filters(): void
    {

        /* NOTE- may need to have some sort of switch/protection for differences in GWDMs 1.0 and 1.1

        $publisherNameFilter = "LOWER(
                JSON_EXTRACT(
                    JSON_UNQUOTE(
                        COALESCE(
                            JSON_EXTRACT(metadata, '$.metadata.summary.publisher.name')
                            JSON_EXTRACT(metadata, '$.metadata.summary.publisher.publisherName'),
                        )
                    ), 
                    '$'
                )
            ) LIKE LOWER(?)";

        */


        $filters = [
            'containsTissue'=>[
                'filter_condition'=>'NOT_YET',
            ],
            'dataType'=>[
                'filter_condition'=> "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.datasetType')) LIKE LOWER(?)",
            ],
            'publisherName'=>[
                'filter_condition'=>"LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.publisher.publisherName')) LIKE LOWER(?)",
            ],
            'collectionName'=>[
                'filter_condition'=>'NOT_YET',
            ],
            'dataUse'=>[
                'filter_condition'=> "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.linkage.dataUses')) LIKE LOWER(?)",
            ],
            'dateRange'=>[
                'filter_condition'=> "JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.provenance.temporal.startDate') >= ? AND JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.provenance.temporal.endDate') <= ?",
            ],
            'populationSize'=>[
                'filter_condition'=> 'NOT_YET',
            ],
            'geographicLocation'=>[
                'filter_condition'=> "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.coverage.spatial')) LIKE LOWER(?)",
            ]
        ];

        $this->seed_filter("dataset",$filters);
    }

     public function seed_datauses_filters(): void
    {
        $filters = [
            'publisherName' => [
                'filter_condition'=>'NOT_YET',
            ],
            'organisationSect' => [
                'filter_condition'=>'NOT_YET',
            ],
            'latestApprovalDate' => [
                'filter_condition'=>'NOT_YET',
            ],
            'accessType' => [
                'filter_condition'=>'NOT_YET',
            ],
        ];


        $this->seed_filter("dataUseRegister",$filters);
    }

    public function seed_tools_filters(): void
    {
        $filters = [
            'programmingLanguage' => [
                "filter_condition" => 'tech_stack LIKE LOWER(?)',
            ],
            'category' => [
                "filter_condition" => 'categories.name LIKE LOWER(?)',
                "join_condition" => [
                    "categories" => "tools.category_id = categories.id"
                ]
            ],
            'category_by_id' => [
                "filter_condition" => 'categories.id = ?',
            ],
            'license' => [
                "filter_condition" =>  'license LIKE LOWER(?)'
            ]
        ];

        $this->seed_filter("tool",$filters);
    }



    public function seed_filter(string $type, array $filters): void
    {
        foreach ($filters as $key => $filter){
            Filter::create([
                'type' => $type, 
                'keys' => $key,
                'value' => $filter["filter_condition"],
                'join_condition' => array_key_exists("join_condition",$filter) ? json_encode($filter['join_condition']) : null,
                'enabled' => true,
            ]);
        }
    }


}
