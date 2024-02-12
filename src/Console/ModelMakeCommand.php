<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ModelMakeCommand as IlluminateModelMakeCommand;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Console\Concerns\ArangoCommands;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class ModelMakeCommand extends IlluminateModelMakeCommand
{
    use ArangoCommands;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:model';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->hasOption('arangodb') &&  $this->option('arangodb')) {
            $this->useArangoDB = true;
        }

        if ($this->useFallback()) {
            return parent::handle();
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }
    }


    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        if ($this->useFallback()) {
            parent::createMigration();
            return;
        }

        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $createCommand = '--create';
        if ($this->option('edge-pivot') || $this->option('edge-morph-pivot')) {
            $createCommand = '--edge';
        }

        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            $createCommand => $table,
            '--fullpath' => true,
        ]);
    }

    protected function getOptions()
    {
        $options = parent::getOptions();
        $options[] = [
            'edge-pivot',
            null,
            InputOption::VALUE_NONE, 'The generated model uses a custom intermediate edge-collection model for ArangoDB'
        ];
        $options[] = [
            'edge-morph-pivot',
            null,
            InputOption::VALUE_NONE, 'The generated model uses a custom polymorphic intermediate edge-collection model for ArangoDB'
        ];
        if (!$this->arangodbIsDefaultConnection()) {
            $options[] = [
                'arangodb',
                null,
                InputOption::VALUE_NONE, 'Use ArangoDB instead of the default connection.'
            ];
        }

        return $options;
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->useFallback()) {
            return parent::getStub();
        }
        if ($this->option('pivot')) {
            return $this->resolveStubPath('/stubs/model.pivot.stub');
        }

        if ($this->option('morph-pivot')) {
            return $this->resolveStubPath('/stubs/model.morph-pivot.stub');
        }

        if ($this->option('edge-pivot')) {
            return $this->resolveStubPath('/stubs/model.edge-pivot.stub');
        }

        if ($this->option('edge-morph-pivot')) {
            return $this->resolveStubPath('/stubs/model.edge-morph-pivot.stub');
        }

        return $this->resolveStubPath('/stubs/model.stub');
    }


    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        if ($this->useFallback()) {
            return parent::resolveStubPath($stub);
        }

        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }
}
