<?php

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use LaravelFreelancerNL\Aranguent\Console\Migrations\AranguentConvertMigrationsCommand;
use Mockery as M;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\Setup\ClassStubs\ApplicationDatabaseMigrationStub;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    //Copy a stub with Illuminate usage to migrations directory
    $stub = __DIR__ . '/../Setup/Database/stubs/test_stub_for_migration_conversion.php';
    $this->conversionMigration = __DIR__ . '/../Setup/Database/Migrations/test_migration_conversion.php';
    copy($stub, $this->conversionMigration);
});

afterEach(function () {
    M::close();

    unlink($this->conversionMigration);
});

/**
 * Illuminate usage is replaced by Aranguent in migration files.
 *
 */
test('illuminate usage is replaced by aranguent in migration files', function () {
    $command = new AranguentConvertMigrationsCommand($migrator = M::mock(Migrator::class));
    $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
    $app->useDatabasePath(__DIR__);
    $command->setLaravel($app);
    $migrator->shouldReceive('getMigrationFiles')->once()->andReturn([
        $this->conversionMigration,
    ]);
    $migrator->shouldReceive('paths')->once()->andReturn([]);

    runCommand($command);

    $content = file_get_contents($this->conversionMigration);
    $occurenceOfAranguent = substr_count($content, 'LaravelFreelancerNL\Aranguent');
    $occurenceOfIlluminate = substr_count($content, 'Illuminate\Database');
    $this->assertEquals(2, $occurenceOfAranguent);
    $this->assertEquals(1, $occurenceOfIlluminate);
});

// Helpers
function runCommand($command, $input = [])
{
    return $command->run(new ArrayInput($input), new NullOutput());
}
