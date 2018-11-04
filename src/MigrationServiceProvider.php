<?php

namespace LaravelFreelancerNL\Aranguent;

use Illuminate\Database\MigrationServiceProvider as IlluminateMigrationServiceProvider;
use LaravelFreelancerNL\Aranguent\Migrations\Migrator;
use LaravelFreelancerNL\Aranguent\Migrations\MigrationCreator;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;

use LaravelFreelancerNL\Aranguent\Console\Migrations\MigrateCommand;
use LaravelFreelancerNL\Aranguent\Console\Migrations\MigrateMakeCommand;

class MigrationServiceProvider extends IlluminateMigrationServiceProvider
{

    /**
     * {@inheritDoc}
     */
    protected $defer = true;

    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
//                MigrateCommand::class,
                MigrateMakeCommand::class,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerRepository();

        $this->registerMigrator();

        $this->registerCreator();

        $this->registerCommands();
    }

    /**
     * {@inheritdoc}
     */
    protected function registerRepository()
    {
        $this->app->singleton('migration.repository', function ($app) {
            $collection = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $collection);
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        $this->app->singleton('migrator', function ($app) {
            $repository = $app['migration.repository'];
            return new Migrator($repository, $app['db'], $app['files']);
        });
    }

    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files']);
        });
    }

    /**
     * Register all of the migration commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $commands = array(
//            'MigrateCommand' => 'command.migrate',
            'MigrateMakeCommand' => 'command.make.migrate',
//            'MigrateFresh' => 'command.migrate.fresh',
//            'MigrateInstall' => 'command.migrate.install',
//            'MigrateRefresh' => 'command.migrate.refresh',
//            'MigrateReset' => 'command.migrate.reset',
//            'MigrateRollback' => 'command.migrate.rollback',
//            'MigrateStatus' => 'command.migrate.status',
        );

        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}"], []);
        }

        $commands = array_keys($commands);
        foreach ($commands as $key => $command) {
            $commands[$key] = "\LaravelFreelancerNL\Aranguent\Console\Migrations\\" . $command;
        }
//        $this->commands($commands);
    }

    /**
     * Register the "migrate" migration command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->extend('command.migrate', function($app) {
            return new MigrateCommand();
        });
    }


    protected function registerMigrateMakeCommand()
    {
        $this->app->extend('command.migrate.make', function () {
            $creator = $this->app['migration.creator'];
            $composer = $this->app->make('Illuminate\Support\Composer');

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    public function provides()
    {
        return [
            'migrator',
            'migration.creator',
            'migration.repository',
//            'command.migrate',
            'command.migrate.make'
        ];
    }
}
