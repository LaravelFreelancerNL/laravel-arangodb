<?php

namespace LaravelFreelancerNL\Aranguent\Providers;

use Illuminate\Database\MigrationServiceProvider as IlluminateMigrationServiceProvider;
use LaravelFreelancerNL\Aranguent\Console\Migrations\AranguentConvertMigrationsCommand;
use LaravelFreelancerNL\Aranguent\Console\Migrations\MigrateMakeCommand;
use LaravelFreelancerNL\Aranguent\Console\ModelAranguentCommand;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use LaravelFreelancerNL\Aranguent\Migrations\MigrationCreator;

class CommandServiceProvider extends IlluminateMigrationServiceProvider
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
                ModelAranguentCommand::class,
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

        $commands = array_merge($this->commands,
            [
                'MigrateMake' => 'command.migrate.make',
                'AranguentConvertMigrations' => 'command.aranguent.convert-migrations',
                'MakeModel' => 'command.model.aranguent',
            ]
        );
        $this->registerCommands($commands);
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

    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('command.migrate.make', function ($app) {
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    protected function registerAranguentConvertMigrationsCommand()
    {
        $this->app->singleton('command.aranguent.convert-migrations', function ($app) {
            return new AranguentConvertMigrationsCommand($app['migrator']);
        });
    }

    protected function registerMakeModelCommand()
    {
        $this->app->singleton('command.model.aranguent', function ($app) {
            return new ModelAranguentCommand($app['files']);
        });
    }

    public function provides()
    {
        return [
            'migrator',
            'migration.creator',
            'migration.repository',
            'command.aranguent.convert-migrations',
            'command.migrate.make',
            'command.model.aranguent',
        ];
    }
}
