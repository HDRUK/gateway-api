<?php

return [
    'audit' => [
        '' => [
            'post' => [
                'methodController' => 'ServiceLayerController@audit',
                'namespaceController' => 'App\Http\Controllers',
            ]
        ]
    ],
    'federations' => [
        '' => [
            'post' => [
                'methodController' => 'DatasetController@store',
                'namespaceController' => 'App\Http\Controllers\Api\V1'
            ],
            'get' => [
                'methodController' => 'DatasetController@getDatasets',
                'namespaceController' => 'App\Http\Controllers\Api\V1'
            ]
        ],
        '{id}' => [
            'patch' => [
                'methodController' => 'DatasetController@setFederationInvalidRunState',
                'namespaceController' => 'App\Http\Controllers\Api\V1'
            ]
        ],
        'update/{pid}' => [ ## silly syntax, we dont need update - thats a given by PATCH
            'patch' => [
                'methodController' => 'DatasetController@updateByPid',
                'namespaceController' => 'App\Http\Controllers\Api\V1'
            ]
        ],
        'delete/{pid}' => [ ## also silly syntax, dont need /delete - thats a given by DELETE
            'patch' => [
                'methodController' => 'DatasetController@updateByPid',
                'namespaceController' => 'App\Http\Controllers\Api\V1'
            ]
        ]
    ],
    'datasets' => [
        '' => [
            'get' => [
                'methodController' => 'ServiceLayerController@getDatasets',
                'namespaceController' => 'App\Http\Controllers'
            ]
        ],
        '{pid}' => [
            'get' => [
                'methodController' => 'ServiceLayerController@getDatasetFromPid',
                'namespaceController' => 'App\Http\Controllers'
            ]
        ]
    ],
    'darq' => [
        '{any}' => [
            'post' => [
                'jwt.verify',
                'check.access:permissions,question-bank.create',
            ],
            'get' => [
                'jwt.verify',
                'check.access:permissions,question-bank.read',
            ],
            'put' => [
                'jwt.verify',
                'check.access:permissions,question-bank.update',
            ],
            'patch' => [
                'jwt.verify',
                'check.access:permissions,question-bank.update',
            ],
            'get' => [
                'jwt.verify',
                'check.access:permissions,question-bank.delete',
            ]
        ],
    ],
    'daras' => [
        '/templates{any?}' => [
            'any' => [ #note: needs updating when permissions are implemented!!
                'jwt.verify',
                #'check.access:permissions,....',
            ],
        ],
        '/applications{any?}' => [
            'post' => [
                'jwt.verify',
                'check.access:permissions,application.create',
            ],
            'any' => [
                'jwt.verify',
                'check.access:permissions,application.read',
            ],
            'put' => [
                'jwt.verify',
                'check.access:permissions,application.update',
            ],
            'patch' => [
                'jwt.verify',
                'check.access:permissions,application.update',
            ],
            'delete' => [
                'jwt.verify',
                'check.access:permissions,application.delete',
            ],
        ]
    ]
];