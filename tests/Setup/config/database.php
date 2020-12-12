<?php

namespace Tests\Setup\Config;

use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;

return [
    'connections' => [
        'arangodb' => [
            'name'                                      => 'arangodb',
            'driver'                                    => 'arangodb',
            ArangoConnectionOptions::OPTION_ENDPOINT    => env('DB_ENDPOINT', 'tcp://localhost:8529'),
            ArangoConnectionOptions::OPTION_AUTH_USER   => env('DB_USERNAME', ''),
            ArangoConnectionOptions::OPTION_AUTH_PASSWD => env('DB_PASSWORD', ''),
            ArangoConnectionOptions::OPTION_CONNECTION  => 'Keep-Alive',
        ],
    ],
];
