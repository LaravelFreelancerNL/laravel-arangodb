<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ModelMakeCommand as IlluminateModelMakeCommand;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Console\Concerns\CommandNameSpace;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;



#[AsCommand(name: 'make:model')]
class ModelMakeCommand extends IlluminateModelMakeCommand
{
    use CommandNameSpace;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:model';

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        ray('ModelMakeCommand construct');
        parent::__construct($files);

        $this->name = $this->prefixCommandNamespace($this->name);
    }

    protected function namespaceCommand(string $command): string
    {
        if (config('arangodb.console_command_namespace') === '') {
            return $command;
        }
        return $command.'.'.config('arangodb.console_command_namespace');
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $createCommand = '--create';
        if ($this->option('edge-pivot') || $this->option('edge-morph-pivot')) {
            $createCommand = '--edge';
        }

        $this->call($this->prefixCommandNamespace('make:migration'), [
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
            InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate edge-collection model'
        ];
        $options[] = [
            'edge-morph-pivot',
            null,
            InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom polymorphic intermediate edge-collection model'
        ];

        return $options;
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('pivot')) {
            ray($this->resolveStubPath('/stubs/model.pivot.stub'));
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
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}
