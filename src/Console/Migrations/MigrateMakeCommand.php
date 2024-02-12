<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console\Migrations;

use Exception;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as IlluminateMigrateMakeCommand;
use Illuminate\Database\Console\Migrations\TableGuesser;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Migrations\MigrationCreator;

class MigrateMakeCommand extends IlluminateMigrateMakeCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--edge= : The edge collection to be created}
        {--table= : The table to alter}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration (Deprecated)}';

    /**
     * Create a new migration install command instance.
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        $this->creator = $creator;
        $this->composer = $composer;

        parent::__construct($creator, $composer);
    }

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle()
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = Str::snake(trim((string) $this->input->getArgument('name')));

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create') ?: false;

        $edge = $this->input->getOption('edge') ?: false;

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the devs
        // to pass a table name into this option as a short-cut for creating.
        if (!$table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        if (!$table && is_string($edge)) {
            $table = $create;

            $edge = true;
        }

        // Next, we will attempt to guess the table name if this the migration has
        // "create" in the name. This will allow us to provide a convenient way
        // of creating migrations that create new tables for the application.
        if (!$table) {
            [$table, $create] = TableGuesser::guess($name);
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $table, $create, $edge);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool  $create
     * @param  bool  $edge
     * @return void
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function writeMigration($name, $table, $create, $edge = false)
    {
        assert($this->creator instanceof MigrationCreator);

        $file = pathinfo(
            $this->creator->create(
                $name,
                $this->getMigrationPath(),
                $table,
                $create,
                $edge
            ),
            PATHINFO_FILENAME
        );

        $this->line("<info>Created Migration:</info> {$file}");
    }
}
