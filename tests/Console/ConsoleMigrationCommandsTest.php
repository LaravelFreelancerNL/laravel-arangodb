<?php

namespace Tests\Console;

use Mockery as M;
use Tests\TestCase;

class ConsoleMigrationCommandsTest extends TestCase
{
    protected $aranguentMigrationStubPath;

    protected $databaseMigrationRepository;

    protected $laravelMigrationPath;

    protected $migrationPath;

    protected $packageMigrationPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packageMigrationPath = __DIR__ . '/../Setup/Database/Migrations';
        $this->aranguentMigrationStubPath = __DIR__ . '/../../stubs';
        $this->laravelMigrationPath = base_path() . '/database/migrations';

        // Clear the make migration test stubs
        array_map('unlink', array_filter((array) glob($this->laravelMigrationPath . '/*')));
    }

    public function tearDown(): void
    {
        M::close();
    }

    /**
     * migrate.
     *
     * @test
     */
    public function migrate()
    {
        $path = 'migrations';

        $this->artisan('migrate', ['--force' => true, '--path' => $path])->run();
    }
}
