<?php

namespace Tests;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\AranguentServiceProvider;
use LaravelFreelancerNL\Aranguent\Testing\TestCase as AranguentTestCase;
use Tests\Setup\TestConfig;

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

    protected array $transactionCollections = [
        'write' => [
            'characters',
            'children',
            'houses',
            'locations',
            'tags',
            'taggables',
        ]
    ];

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        TestConfig::set($app);
    }

    protected function getPackageAliases($app)
    {
        return [
            'Aranguent' => 'LaravelFreelancerNL\Aranguent',
        ];
    }

    protected function getPackageProviders($app)
    {
        return [
            AranguentServiceProvider::class,
        ];
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
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->tearDownTheTestEnvironment();
    }


    protected function skipTestOnArangoVersionsBefore(string $version)
    {
        if (version_compare(getenv('ARANGODB_VERSION'), $version, '<')) {
            $this->markTestSkipped('This test does not support ArangoDB versions before ' . $version);
        }
    }
}
