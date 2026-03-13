<?php

return [
    'schema' => [
        'url' => env('FORM_HYDRATION_SCHEMA_URL', "https://raw.githubusercontent.com/HDRUK/schemata/master/docs/%s/%s.form.json"),
        'model' => env('FORM_HYDRATION_SCHEMA_MODEL', 'HDRUK'),
        'latest_version' => env('FORM_HYDRATION_SCHEMA_LATEST_VERSION', '3.0.0')
    ],
];