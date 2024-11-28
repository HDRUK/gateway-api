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
            "delete" => [
                'jwt.verify',
                'check.access:permissions,question-bank.delete',
            ]
        ],
    ],
    "daras" => [
        "/templates{any?}" => [
            "post" => [
                'jwt.verify',
                'check.access:permissions,data-access-template.create',
            ],
            "get" => [
                'jwt.verify',
                'check.access:permissions,data-access-template.read',
            ],
            "put" => [
                'jwt.verify',
                'check.access:permissions,data-access-template.update',
            ],
            "patch" => [
                'jwt.verify',
                'check.access:permissions,data-access-template.update',
            ],
            "delete" => [
                'jwt.verify',
                'check.access:permissions,data-access-template.delete',
            ]
        ],
        "/applications{any?}" => [
            "post" => [
                'jwt.verify',
                'check.access:permissions,data-access-applications.applicant.create',
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
