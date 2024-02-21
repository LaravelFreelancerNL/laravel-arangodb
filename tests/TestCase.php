<?php

namespace Tests;

use ArangoClient\Schema\SchemaManager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use LaravelFreelancerNL\Aranguent\AranguentServiceProvider;
use Tests\Setup\TestConfig;
use LaravelFreelancerNL\Aranguent\Testing\TestCase as AranguentTestCase;

class TestCase extends AranguentTestCase implements \Orchestra\Testbench\Contracts\TestCase
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

    protected SchemaManager $schemaManager;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            AranguentServiceProvider::class,
        ];
    }

    /**
     * Ignore package discovery from.
     *
     * @return array<int, string>
     */
    public function ignorePackageDiscoveriesFrom()
    {
        return [];
    }

    /**
     * Override application aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<string, class-string<\Illuminate\Support\Facades\Facade>>
     */
    protected function getPackageAliases($app)
    {
        return [
            'Aranguent' => 'LaravelFreelancerNL\Aranguent',
        ];
    }


    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {

        $this->setTransactionCollections([
            'write' => [
                'characters',
                'children',
                'houses',
                'locations',
                'tags',
                'taggables',
                'users'
            ]]);

        parent::setUp();

        $this->connection = DB::connection();

        //Convert orchestra migrations
        $this->artisan(
            'convert:migrations',
            ['--realpath' => true, '--path' => __DIR__ . '/../vendor/orchestra/testbench-core/laravel/migrations/']
        )->run();

        $this->setTransactionCollections([
            'write' => [
                'characters',
                'children',
                'houses',
                'locations',
                'tags',
                'taggables',
            ]
        ]);
    }

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
    protected function skipTestOnArangoVersionsBefore(string $version)
    {
        if (version_compare(getenv('ARANGODB_VERSION'), $version, '<')) {
            $this->markTestSkipped('This test does not support ArangoDB versions before ' . $version);
        }
    }

    public function clearDatabase()
    {
        $collections  = $this->schemaManager->getCollections(true);
        foreach($collections as $collection) {
            $this->schemaManager->deleteCollection($collection->name);
        }
    }
}
