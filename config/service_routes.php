<?php

return [
    "darq" => [
        "{any}" => [
            "post" => [
                'jwt.verify',
                'check.access:permissions,question-bank.create',
            ],
            "get" => [
                'jwt.verify',
                'check.access:permissions,question-bank.read',
            ],
            "put" => [
                'jwt.verify',
                'check.access:permissions,question-bank.update',
            ],
            "patch" => [
                'jwt.verify',
                'check.access:permissions,question-bank.update',
            ],
            "get" => [
                'jwt.verify',
                'check.access:permissions,question-bank.delete',
            ]
        ],
    ],
    "daras" => [
        "/templates{any?}" => [
            "any" => [ #note: needs updating when permissions are implemented!!
                'jwt.verify',
                #'check.access:permissions,....',
            ],
        ],
        "/applications{any?}" => [
            "post" => [
                'jwt.verify',
                'check.access:permissions,application.create',
            ],
            "any" => [
                'jwt.verify',
                'check.access:permissions,application.read',
            ],
            "put" => [
                'jwt.verify',
                'check.access:permissions,application.update',
            ],
            "patch" => [
                'jwt.verify',
                'check.access:permissions,application.update',
            ],
            "delete" => [
                'jwt.verify',
                'check.access:permissions,application.delete',
            ],
        ]
    ]
];
