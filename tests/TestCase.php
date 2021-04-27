<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\AranguentServiceProvider;
use LaravelFreelancerNL\Aranguent\Connection as Connection;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use Mockery as m;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $collection = 'migrations';

    protected $connection;

    protected $schemaManager;

    protected $databaseMigrationRepository;

    protected $packageMigrationPath;

    protected $aranguentMigrationStubPath;

    protected $laravelMigrationPath;

    /**
     * Define environment setup.
     *
     * @param Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__ . '/Setup/config/database.php';
        $app['config']->set('database.connections.arangodb', $config['connections']['arangodb']);
        $app['config']->set('database.default', 'arangodb');
        $app['config']->set('cache.driver', 'array');

        $this->connection = DB::connection('arangodb');
        $this->schemaManager = $this->connection->getArangoClient()->schema();

        $this->createDatabase();
        $this->connection->setDatabaseName(
            ($app['config']['database.database']) ? $app['config']['database.database'] : 'aranguent__test'
        );


        //Truncate all collections
        $collections = $this->schemaManager->getCollections(true);
        foreach ($collections as $collection) {
            $this->schemaManager->truncateCollection($collection->id);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();

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
        if (! $this->schemaManager->hasDatabase($database)) {
            $this->schemaManager->createDatabase($database);

            return true;
        }

        return false;
    }

    protected function migrate()
    {
        //Convert orchestra migrations
        $this->artisan(
            'aranguent:convert-migrations',
            ['--realpath' => true, '--path' => __DIR__ . '/../vendor/orchestra/testbench-core/laravel/migrations/']
        )
            ->run();

        $this->installMigrateIfNotExists();

        $this->artisan('migrate', [
            '--path'     => realpath(__DIR__ . '/Setup/Database/Migrations'),
            '--realpath' => true,
        ])->run();
    }

    private function installMigrateIfNotExists()
    {
        if (! $this->schemaManager->hasCollection('migrations')) {
            $this->artisan('migrate:install', [])->run();
        }
    }

    protected function getBuilder()
    {
        $grammar = new Grammar();
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(Connection::class), $grammar, $processor);
    }

    protected function skipTestOnArangoVersionsBefore(string $version)
    {
        if (version_compare(getenv('ARANGODB_VERSION'), $version, '<')) {
            $this->markTestSkipped('This test does not support ArangoDB versions before ' . $version);
        }
    }
}
