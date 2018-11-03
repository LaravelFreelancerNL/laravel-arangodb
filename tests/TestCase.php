<?php

namespace LaravelFreelancerNL\Aranguent\Tests;

use ArangoDBClient\Database;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\AranguentServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    protected $connection;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = require 'config/database.php';

        $app['config']->set('database.default', 'arangodb');
        $app['config']->set('database.connections.arangodb', $config['connections']['arangodb']);
        $app['config']->set('database.connections.mysql', $config['connections']['mysql']);
        $app['config']->set('database.connections.sqlite', $config['connections']['sqlite']);

        $app['config']->set('cache.driver', 'array');

        $this->connection = DB::connection('arangodb');
    }

    protected function getPackageProviders($app)
    {
        return [
            AranguentServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Aranguent' => 'LaravelFreelancerNL\Aranguent'
        ];
    }
}
