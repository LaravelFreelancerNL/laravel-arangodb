<?php

namespace LaravelFreelancerNL\Aranguent\Console;

use Illuminate\Foundation\Console\ModelMakeCommand as IlluminateModelMakeCommand;

class ModelAranguentCommand extends IlluminateModelMakeCommand
{
    protected $deferred = true;
    protected $defer = true;

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('pivot')) {
            return __DIR__ . '/stubs/pivot.model.stub';
        }

        return __DIR__ . '/stubs/model.stub';
    }
}
