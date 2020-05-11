<?php

namespace Tests;

use ArangoDBClient\Database;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\AranguentServiceProvider;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $collection = 'migrations';

    protected $connection;

    protected $collectionHandler;

    protected $databaseMigrationRepository;

    protected $packageMigrationPath;

    protected $aranguentMigrationStubPath;

    protected $laravelMigrationPath;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = require 'config/database.php';
        $app['config']->set('database.connections.arangodb', $config['connections']['arangodb']);
        $app['config']->set('database.default', 'arangodb');
        $app['config']->set('cache.driver', 'array');

        $this->connection = DB::connection('arangodb');

        $this->createDatabase();
        $this->connection->setDatabaseName(($app['config']['database.database']) ? $app['config']['database.database'] : 'aranguent__test');

        $this->collectionHandler = $this->connection->getCollectionHandler();
        //Remove all collections
        $collections = $this->collectionHandler->getAllCollections(['excludeSystem' => true]);
        foreach ($collections as $collection) {
            $this->collectionHandler->truncate($collection['id']);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withFactories(realpath(__DIR__.'/database/factories'));

        $this->artisan('aranguent:convert-migrations', ['--realpath' => true, '--path' => __DIR__.'/../vendor/orchestra/testbench-core/laravel/migrations/'])->run();

        //Running migrations without loading them through testbench as it does something weird which doesn't save the new collections
        $this->installMigrateIfNotExists();

        $this->artisan('migrate', [
            '--path' => realpath(__DIR__.'/database/migrations'),
            '--realpath' => true,
        ])->run();

        $this->artisan('aranguent:model', [
            'name' => 'Character',
        ])->run();

        $this->databaseMigrationRepository = new DatabaseMigrationRepository($this->app['db'], $this->collection);
    }

    protected function getPackageProviders($app)
    {
        return [
            AranguentServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Aranguent' => 'LaravelFreelancerNL\Aranguent',
        ];
    }

    protected function createDatabase($database = 'aranguent__test')
    {
        $databaseHandler = new Database();
        $response = $databaseHandler->listUserDatabases($this->connection->getArangoConnection());
        if (! in_array($database, $response['result'])) {
            $databaseHandler->create($this->connection->getArangoConnection(), $database);

            return true;
        }

        return false;
    }

    private function installMigrateIfNotExists()
    {
        $collections = $this->collectionHandler->getAllCollections(['excludeSystem' => true]);
        if (! isset($collections['migrations'])) {
            $this->artisan('migrate:install', [])->run();
        }
    }
}
