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
        $keys = [
            'containsTissue',
            'dataType',
            'publisherName',
            'collectionName',
            'dataUse',
            'dateRange',
            'populationSize',
            'geographicLocation',
        ];

        $code = [
            'NOT_YET',
            "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.datasetType')) LIKE LOWER(?)",
            "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.publisher.publisherName')) LIKE LOWER(?)",
            'NOT_YET',
            "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.linkage.dataUses')) LIKE LOWER(?)",
            "JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.provenance.temporal.startDate') >= ? AND JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.provenance.temporal.endDate') <= ?",
            'NOT_YET',
            "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.coverage.spatial')) LIKE LOWER(?)",
        ];

        for($i = 0; $i < count($keys); $i++) {
            Filter::create([
                'type' => 'dataset', // hardcoded while there is only one
                'keys' => $keys[$i],
                'value' => $code[$i],
                'enabled' => true,
            ]);
        }
    }
}
