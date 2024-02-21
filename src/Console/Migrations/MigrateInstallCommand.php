<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console\Migrations;

use Illuminate\Database\Console\Migrations\InstallCommand as IlluminateInstallCommand;
use LaravelFreelancerNL\Aranguent\Console\Concerns\ArangoCommands;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;

class MigrateInstallCommand extends IlluminateInstallCommand
{
    use ArangoCommands;

    /**
     * The repository instance.
     *
     * @var DatabaseMigrationRepository
     */
    protected $repository;


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $database = $this->input->getOption('database');

        /** @phpstan-ignore-next-line */
        $this->repository->useArangodb = $this->connectionHasArangodbDriver($database);

        parent::handle();
    }
}
