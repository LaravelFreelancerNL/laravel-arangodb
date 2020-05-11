<?php

namespace Tests\Config;

use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;

return [
    'connections' => [
        'arangodb' => [
            'name'       => 'arangodb',
            'driver'     => 'arangodb',
            ArangoConnectionOptions::OPTION_ENDPOINT => env('ARANGODB_ENDPOINT', 'tcp://localhost:8529'),
            ArangoConnectionOptions::OPTION_AUTH_USER => env('ARANGODB_AUTH_USER', ''),
            ArangoConnectionOptions::OPTION_AUTH_PASSWD => env('ARANGODB_AUTH_PASSWD', ''),
            ArangoConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
        ],
    ],
];
