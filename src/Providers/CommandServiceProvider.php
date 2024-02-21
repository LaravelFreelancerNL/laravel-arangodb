<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Providers;

use LaravelFreelancerNL\Aranguent\Console\DbCommand;
use Illuminate\Database\Console\DbCommand as IlluminateDbCommand;
use LaravelFreelancerNL\Aranguent\Console\ModelMakeCommand;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    /**
     * The commands to be registered.
     *
     * @var string[]
     */
    protected $commands = [
        'ModelMake' => ModelMakeCommand::class,
        'Db' => DbCommand::class,
    ];


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands($this->commands);
    }

    /**
     * Register the given commands.
     *
     * @param  string[]  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            $this->{"register{$command}Command"}();
        }

        $this->commands(array_values($commands));
    }

    protected function registerModelMakeCommand(): void
    {
        $this->app->singleton(ModelMakeCommand::class, function ($app) {
            return new ModelMakeCommand($app['files']);
        });
    }

    protected function registerDbCommand(): void
    {
        $this->app->extend(IlluminateDbCommand::class, function () {
            return new DbCommand();
        });
    }

    /**
     * @return string[]
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}
