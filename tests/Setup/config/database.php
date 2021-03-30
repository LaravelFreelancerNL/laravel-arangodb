<?php

namespace Tests\Setup\Config;

return [
    'connections' => [
        'arangodb' => [
            'name'                                      => 'arangodb',
            'driver'                                    => 'arangodb',
            'endpoint'                                  => env('DB_ENDPOINT', 'http://localhost:8529'),
            'username'                                  => env('DB_USERNAME', 'root'),
            'password'                                  => env('DB_PASSWORD', null),
        ],
    ],
];
