<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Providers;

use LaravelFreelancerNL\Aranguent\Console\ShowCommand;
use LaravelFreelancerNL\Aranguent\Console\ShowModelCommand;
use LaravelFreelancerNL\Aranguent\Console\TableCommand;
use LaravelFreelancerNL\Aranguent\Console\WipeCommand;
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
        'DbWipe' => WipeCommand::class,
        'DbShow' => ShowCommand::class,
        'DbTable' => TableCommand::class,
        'ShowModel' => ShowModelCommand::class,
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
     *
     * @SuppressWarnings("PHPMD.ElseExpression")
     */
    protected function registerCommands(array $commands)
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->app->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    protected function registerDbCommand(): void
    {
        $this->app->extend(IlluminateDbCommand::class, function () {
            return new DbCommand();
        });
    }

    protected function registerModelMakeCommand(): void
    {
        $this->app->singleton(ModelMakeCommand::class, function ($app) {
            return new ModelMakeCommand($app['files']);
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
