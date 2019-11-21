<?php

namespace LaravelFreelancerNL\Aranguent\test;

use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;

return [
    'connections' => [
        'arangodb' => [
            'name'       => 'arangodb',
            'driver'     => 'arangodb',
            ArangoConnectionOptions::OPTION_DATABASE => 'aranguent__test',
            ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://arangodb:8529',
            ArangoConnectionOptions::OPTION_AUTH_USER => '',
            ArangoConnectionOptions::OPTION_AUTH_PASSWD => '',
            ArangoConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
        ],
    ],
];
