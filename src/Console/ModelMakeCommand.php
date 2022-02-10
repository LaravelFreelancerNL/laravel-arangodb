<?php

namespace LaravelFreelancerNL\Aranguent\Console;

use Illuminate\Foundation\Console\ModelMakeCommand as IlluminateModelMakeCommand;

class ModelMakeCommand extends IlluminateModelMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'aranguent:model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('pivot')
            ? $this->resolveStubPath(__DIR__ . '/../../stubs/pivot.model.stub')
            : $this->resolveStubPath(__DIR__ . '/../../stubs/model.stub');
    }
}
