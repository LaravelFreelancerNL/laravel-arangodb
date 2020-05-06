<?php
namespace Tests\Config;

use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;

return [
    'connections' => [
        'arangodb' => [
            'name'       => 'arangodb',
            'driver'     => 'arangodb',
            ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://localhost:8529',
            ArangoConnectionOptions::OPTION_AUTH_USER => 'root',
            ArangoConnectionOptions::OPTION_AUTH_PASSWD => '',
            ArangoConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
        ],
    ],
];
