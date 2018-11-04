<?php

namespace LaravelFreelancerNL\Aranguent\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Console\Migrations\MigrateCommand as IlluminateMigrateCommand;

class MigrateCommand extends IlluminateMigrateCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--path= : The path to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--pretend : Dump the SQL queries that would be run}
                {--seed : Indicates if the seed task should be re-run}
                {--step : Force the migrations to be run so they can be rolled back individually}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations';

    /**
     * The migrator instance.
     *
     * @var \LaravelFreelancerNL\Aranguent\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration command instance.
     *
     * @param  \LaravelFreelancerNL\Aranguent\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct()
    {
        $this->migrator = app()->get('migrator');

        parent::__construct($this->migrator);
    }
}
