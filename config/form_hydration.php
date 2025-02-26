<?php

use App\Services\SchemaVersionsService;

$schemaData = SchemaVersionsService::getSchemaVersions();
return [
    'schema' => [
        'url' => env('FORM_HYDRATION_SCHEMA_URL', "https://raw.githubusercontent.com/HDRUK/schemata-2/master/docs/%s/%s.form.json"),
        'model' => $schemaData['FORM_HYDRATION_SCHEMA_MODEL'],
        'latest_version' => $schemaData['FORM_HYDRATION_SCHEMA_LATEST_VERSION'],
    ],
];
