<?php

declare(strict_types=1);

namespace Tests\Setup;

class TestConfig
{
    public static function set($app)
    {
        $app['config']->set('database.connections.arangodb', [
            'name'                                      => 'arangodb',
            'driver'                                    => 'arangodb',
            'endpoint'                                  => env('DB_ENDPOINT', 'http://localhost:8529'),
            'username'                                  => env('DB_USERNAME', 'root'),
            'password'                                  => env('DB_PASSWORD', null),
            'database'                                  => env('DB_DATABASE', 'aranguent__test'),
        ]);
        $app['config']->set('database.default', 'arangodb');
    }
}
