<?php

use App\Services\SchemaVersionsService;

$schemaData = SchemaVersionsService::getSchemaVersions();
return [
    "GWDM" => [
        "name" => $schemaData['GWDM'],
        "version" =>  $schemaData['GWDM_CURRENT_VERSION']
    ]
];
