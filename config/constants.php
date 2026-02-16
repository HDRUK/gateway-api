<?php

return [
    'provider' => [
        'service' => 'service',
        'cruk' => 'cruk',
        'internal' => 'internal',
        'google' => 'google',
        'linkedin' => 'linkedin',
        'azure' => 'azure',
    ],
    'test' => [
        'user' => [
            'name' => 'John Doe',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe.1234567890@example.com',
            'password' => 'passw@rdJ0hnD0e',
            'is_admin' => 1
        ],
        'non_admin' => [
            'name' => 'Alice Smith',
            'firstname' => 'Alice',
            'lastname' => 'Smith',
            'email' => 'alice.smith.1234567890@example.com',
            'password' => 'passw@rdAlic3',
            'is_admin' => 0
        ],
        'non_admin_2' => [
            'name' => 'Bob Dylan',
            'firstname' => 'Bob',
            'lastname' => 'Dylan',
            'email' => 'bob.dylan.1234567890@example.com',
            'password' => 'passw@rdB0bDy1an',
            'is_admin' => 0
        ],
    ],
    'per_page' => 25,
];
