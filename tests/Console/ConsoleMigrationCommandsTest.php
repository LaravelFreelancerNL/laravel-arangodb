<?php

use Mockery as M;
use LaravelFreelancerNL\Aranguent\Tests\TestCase;

class ConsoleMigrationCommandsTest extends TestCase
{
    protected $packageMigrationPath;

    protected $aranguentMigrationStubPath;

    protected $laravelMigrationPath;

    protected $migrationPath;

    protected function setUp()
    {
        parent::setUp();

        $this->packageMigrationPath = __DIR__.'/database/migrations';
        $this->aranguentMigrationStubPath = __DIR__.'/../src/Migrations/stubs';
        $this->laravelMigrationPath = base_path().'/database/migrations';

        // Clear the make migration test stubs
        array_map('unlink', array_filter((array) glob($this->laravelMigrationPath.'/*')));
    }

    public function tearDown()
    {
        M::close();
    }

    /**
     * migrate.
     * @test
     */
    public function migrate()
    {
        $path = 'migrations';

        $this->artisan('migrate', ['--force' => true, '--path' => $path])->run();
    }

//    /**
//     * migrate install command
//     * @test
//     */
//    function migrate_install_command()
//    {
//        $this->assertTrue($this->collectionHandler->has('migrations'));
//    }
//
//    /**
//     * make migration
//     * @test
//     */
//    function make_migration_command_generates_migration_files()
//    {
//        $this->artisan('make:migration', ['name' => 'blank_comments_collection'])->run();
//        $this->artisan('make:migration', ['name' => 'create_comments_collection', '--create' => 'comments'])->run();
//        $this->artisan('make:migration', ['name' => 'update_comments_collection', '--collection' => 'comments'])->run();
//
//        $files = scandir($this->laravelMigrationPath, SCANDIR_SORT_DESCENDING);
//        $migratedUpdateStubPath = $this->laravelMigrationPath . '/' .  $files[0];
//        $migratedCreateStubPath = $this->laravelMigrationPath . '/' . $files[1];
//        $migratedBlankStubPath = $this->laravelMigrationPath . '/' . $files[2];
//
//        $blankContent = file_get_contents($migratedBlankStubPath);
//        $createContent = file_get_contents($migratedCreateStubPath);
//        $updateContent = file_get_contents($migratedUpdateStubPath);
//
//        $this->assertFileExists($migratedBlankStubPath);
//        $this->assertFileExists($migratedCreateStubPath);
//        $this->assertFileExists($migratedUpdateStubPath);
//
//        $this->assertRegExp('/Aranguent/', $blankContent);
//        $this->assertRegExp('/CreateCommentsCollection/', $createContent);
//        $this->assertRegExp('/Schema::create\(\'comments\', function \(Blueprint \$collection\)/', $createContent);
//        $this->assertRegExp('/UpdateCommentsCollection/', $updateContent);
//        $this->assertRegExp('/Schema::collection\(\'comments\', function \(Blueprint \$collection\)/', $updateContent);
//    }
//
//    /**
//     * migrate status command
//     * @test
//     */
//    function migrate_status_command()
//    {
//        $this->artisan('migrate:status', [])->run();
//    }
//
//    /**
//     * migrate rollback command
//     * @test
//     */
//    function migrate_rollback_command()
//    {
//        $this->databaseMigrationRepository->log('migrateRollbackTest', 1);
//        $this->databaseMigrationRepository->log('migrateRollbackTest', 2);
//        $this->databaseMigrationRepository->log('migrateRollbackTest', 3);
//
//        $this->artisan('migrate:rollback', ['--force' => true, '--step' => 2])->run();
//    }
}
