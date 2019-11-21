<?php

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use LaravelFreelancerNL\Aranguent\Console\Migrations\AranguentConvertMigrationsCommand;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;
use Mockery as M;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class AranguentConvertMigrationsCommandTest extends TestCase
{
    protected $packageMigrationPath;

    protected $aranguentMigrationStubPath;

    protected $laravelMigrationPath;

    protected $migrationPath;

    protected $conversionMigration;

    protected function setUp() : void
    {
        parent::setUp();

        //Copy a stub with Illuminate usage to migrations directory
        $stub = __DIR__.'/../database/stubs/test_stub_for_migration_conversion.php';
        $this->conversionMigration = __DIR__.'/../database/migrations/test_migration_conversion.php';
        copy($stub, $this->conversionMigration);
    }

    public function tearDown() : void
    {
        M::close();

        unlink($this->conversionMigration);
    }

    /**
     * Illuminate usage is replaced by Aranguent in migration files.
     * @test
     */
    public function illuminate_usage_is_replaced_by_aranguent_in_migration_files()
    {
        $command = new AranguentConvertMigrationsCommand($migrator = M::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('getMigrationFiles')->once()->andReturn([
            $this->conversionMigration,
        ]);
        $migrator->shouldReceive('paths')->once()->andReturn([]);

        $this->runCommand($command);

        $content = file_get_contents($this->conversionMigration);
        $occurenceOfAranguent = substr_count($content, 'LaravelFreelancerNL\Aranguent');
        $occurenceOfIlluminate = substr_count($content, 'Illuminate\Database');
        $this->assertEquals(2, $occurenceOfAranguent);
        $this->assertEquals(1, $occurenceOfIlluminate);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseMigrationStub extends Application
{
    public function __construct(array $data = [])
    {
        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment(...$environments)
    {
        return 'development';
    }
}
