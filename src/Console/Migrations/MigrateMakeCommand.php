<?php

namespace LaravelFreelancerNL\Aranguent\Console\Migrations;

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
        {--create= : The collection to be created}
        {--collection= : The collection to migrate}
        {--table= : (Alias for collection)}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}';

    /**
     * Create a new migration install command instance.
     *
     * @param  MigrationCreator  $creator
     * @param  Composer  $composer
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
     * @throws \Exception
     */
    public function handle()
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = Str::snake(trim($this->input->getArgument('name')));

        $collection = $this->input->getOption('collection');
        if (! $collection) {
            $collection = $this->input->getOption('table');
        }

        $create = $this->input->getOption('create') ?: false;

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the devs
        // to pass a table name into this option as a short-cut for creating.
        if (! $collection && is_string($create)) {
            $collection = $create;

            $create = true;
        }

        // Next, we will attempt to guess the table name if this the migration has
        // "create" in the name. This will allow us to provide a convenient way
        // of creating migrations that create new tables for the application.
        if (! $collection) {
            [$collection, $create] = TableGuesser::guess($name);
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $collection, $create);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $collection
     * @param  bool  $create
     * @return void
     *
     * @throws \Exception
     */
    protected function writeMigration($name, $collection, $create)
    {
        $file = pathinfo($this->creator->create(
            $name,
            $this->getMigrationPath(),
            $collection,
            $create
        ), PATHINFO_FILENAME);

        $this->line("<info>Created Migration:</info> {$file}");
    }
}
