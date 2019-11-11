<?php

namespace LaravelFreelancerNL\Aranguent;

use LaravelFreelancerNL\Aranguent\Migrations\MigrationCreator;
use LaravelFreelancerNL\Aranguent\Console\Migrations\MigrateMakeCommand;
use LaravelFreelancerNL\Aranguent\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\MigrationServiceProvider as IlluminateMigrationServiceProvider;
use LaravelFreelancerNL\Aranguent\Console\Migrations\AranguentConvertMigrationsCommand;

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

        $commands = array_merge($this->commands,
            [
                'MigrateMake' => 'command.migrate.make',
                'AranguentConvertMigrations' => 'command.aranguent.convert-migrations',
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

//    /**
//     * Register all of the migration commands.
//     *
//     * @param array $commands
//     * @return void
//     */
//    protected function registerCommands(array $commands)
//    {
//        $commands = array_keys($commands);
//        foreach ($commands as $key => $command) {
//            $commands[$key] = "\LaravelFreelancerNL\Aranguent\Console\Migrations\\".$command;
//        }
//        $this->commands([
//            'command.aranguent.convert-migrations',
//        ]);
//    }


    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('command.migrate.make', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
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
