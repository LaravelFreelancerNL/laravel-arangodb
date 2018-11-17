<?php

namespace LaravelFreelancerNL\Aranguent;

use Illuminate\Database\Migrations\Migrator;
use LaravelFreelancerNL\Aranguent\Console\Migrations\AranguentConvertMigrationsCommand;
use LaravelFreelancerNL\Aranguent\Migrations\MigrationCreator;
use LaravelFreelancerNL\Aranguent\Console\Migrations\MigrateMakeCommand;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\MigrationServiceProvider as IlluminateMigrationServiceProvider;

class MigrationServiceProvider extends IlluminateMigrationServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $defer = true;

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
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
        $commands = [
            'MigrateMakeCommand' => 'command.make.migrate',
            'AranguentConvertMigrationsCommand' => 'command.aranguent.convert-migrations',
        ];

        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}"], []);
        }

        $commands = array_keys($commands);
        foreach ($commands as $key => $command) {
            $commands[$key] = "\LaravelFreelancerNL\Aranguent\Console\Migrations\\".$command;
        }
        $this->commands([
            'command.aranguent.convert-migrations',
        ]);
    }

    protected function registerMigrateMakeCommand()
    {
        $this->app->extend('command.migrate.make', function () {
            $creator = $this->app['migration.creator'];
            $composer = $this->app->make('Illuminate\Support\Composer');

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    protected function registerAranguentConvertMigrationsCommand()
    {
        $this->app->singleton('command.aranguent.convert-migrations', function($app) {
            return new AranguentConvertMigrationsCommand($app['migrator']);
        });
    }

    public function provides()
    {
        return [
            'migrator',
            'migration.creator',
            'migration.repository',
            'command.migrate.make',
            'command.aranguent.convert-migrations',
        ];
    }
}
