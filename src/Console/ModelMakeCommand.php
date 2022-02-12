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
            ? $this->resolveStubPath('/../../stubs/pivot.model.stub')
            : $this->resolveStubPath('/../../stubs/model.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * This method is an exact copy of the original to keep the functionality but reroute __DIR__
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }
}
