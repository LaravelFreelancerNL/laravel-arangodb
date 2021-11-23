<?php

namespace Tests;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\AranguentServiceProvider;
use LaravelFreelancerNL\Aranguent\Connection as Connection;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\Aranguent\Query\Processor;
use LaravelFreelancerNL\Aranguent\Testing\TestCase as AranguentTestCase;
use Mockery as m;

abstract class TestCase extends AranguentTestCase implements \Orchestra\Testbench\Contracts\TestCase
{
    use OrchestraTestbenchTesting;

    protected ?ConnectionInterface $connection;

    protected bool $dropViews = true;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'arangodb');
        $app['config']->set('database.connections.arangodb', [
            'name'                                      => 'arangodb',
            'driver'                                    => 'arangodb',
            'endpoint'                                  => env('DB_ENDPOINT', 'http://localhost:8529'),
            'username'                                  => env('DB_USERNAME', 'root'),
            'password'                                  => env('DB_PASSWORD', null),
            'database'                                  => env('DB_DATABASE', 'aranguent__test'),
        ]);
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setUpTheTestEnvironment();

        $this->connection = DB::connection();

        //Convert orchestra migrations
        $this->artisan(
            'aranguent:convert-migrations',
            ['--realpath' => true, '--path' => __DIR__ . '/../vendor/orchestra/testbench-core/laravel/migrations/']
        )->run();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->tearDownTheTestEnvironment();
    }

    /**
     * Boot the testing helper traits.
     *
     * @return array<string, string>
     */
    protected function setUpTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        return $this->setUpTheTestEnvironmentTraits($uses);
    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();
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

    protected function skipTestOnArangoVersionsBefore(string $version)
    {
        if (version_compare(getenv('ARANGODB_VERSION'), $version, '<')) {
            $this->markTestSkipped('This test does not support ArangoDB versions before ' . $version);
        }
    }

    protected function getBuilder()
    {
        $grammar = new Grammar();
        $processor = m::mock(Processor::class);

        return new Builder(m::mock(Connection::class), $grammar, $processor);
    }
}
