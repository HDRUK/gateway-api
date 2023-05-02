<?php

use Illuminate\Support\Facades\Facade;

return [
    /*
    |--------------------------------------------------------------------------
    | Filter types
    |--------------------------------------------------------------------------
    |
    | We wouldn't ordinarily put this in global scope, but considering the
    | length of these arrays and the requirement of having them defined
    | in multiple places, it makes sense to write once, rather than
    | duplicate where needed.
    |
    */
    'types' => [
        'dataset',
        'collection',
        'tool',
        'course',
        'project',
        'paper',
        'dataUseRegister',
    ],
];
