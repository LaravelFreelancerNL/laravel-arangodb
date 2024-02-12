<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Providers;

use LaravelFreelancerNL\Aranguent\Console\ModelMakeCommand;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    protected bool $defer = true;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'ModelMake' => ModelMakeCommand::class,
    ];


    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                'ModelMake' => ModelMakeCommand::class,
            ]);
        }
    }

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
     * @param  array  $commands
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

    /**
     * @return string[]
     */
    public function provides()
    {
        return [
            ModelMakeCommand::class
        ];
    }
}
