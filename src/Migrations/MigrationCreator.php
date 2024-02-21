<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Migrations;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Migrations\MigrationCreator as IlluminateMigrationCreator;
use Illuminate\Filesystem\Filesystem;
use LaravelFreelancerNL\Aranguent\Console\Concerns\ArangoCommands;

class MigrationCreator extends IlluminateMigrationCreator
{
    use ArangoCommands;

    /**
     * The custom app stubs directory.
     *
     * @var string
     */
    protected $customStubPath;

    protected string $arangoStubPath = __DIR__ . '/stubs';

    /**
     * Create a new migration creator instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $customStubPath
     * @return void
     */
    public function __construct(Filesystem $files, $customStubPath = __DIR__ . '/stubs')
    {
        parent::__construct($files, $customStubPath);
    }


    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        if ($this->useFallback()) {
            return parent::stubPath();
        }

        return __DIR__ . '/stubs';
    }

    /**
     * Get the migration stub file.
     *
     * @param string|null $table
     * @param bool $create
     * @param bool $edge
     * @return string
     *
     * @throws FileNotFoundException
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function getStub($table, $create, $edge = false)
    {
        if ($this->useFallback()) {
            return parent::getStub($table, $create);
        }

        if (is_null($table)) {
            $stub = $this->files->exists($customPath = $this->customStubPath . '/migration.stub')
                ? $customPath
                : $this->stubPath() . '/migration.stub';
            return $this->files->get($stub);
        } elseif ($edge) {
            $stub = $this->files->exists($customPath = $this->customStubPath . '/migration.create-edge.stub')
                ? $customPath
                : $this->stubPath() . '/migration.create-edge.stub';


            return $this->files->get($stub);
        } elseif ($create) {
            $stub = $this->files->exists($customPath = $this->customStubPath . '/migration.create.stub')
                ? $customPath
                : $this->stubPath() . '/migration.create.stub';

            return $this->files->get($stub);
        }


        $stub = $this->files->exists($customPath = $this->customStubPath . '/migration.update.stub')
            ? $customPath
            : $this->stubPath() . '/migration.update.stub';

        return $this->files->get($stub);
    }

    /**
     * Create a new migration at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string|null  $table
     * @param  bool  $create
     * @param  bool  $edge
     * @return string
     *
     * @throws \Exception
     */
    /**  @phpstan-ignore-next-line  @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function create($name, $path, $table = null, $create = false, $edge = false)
    {
        if ($this->useFallback()) {
            return parent::create($name, $path, $table = null, $create = false);
        }

        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $stub = $this->getStub($table, $create, $edge);

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path,
            $this->populateStub($stub, $table)
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks($table, $path);

        return $path;
    }

    public function getDefaultIlluminateCustomStubPath(): string
    {
        return base_path('vendor/laravel/framework/src/Illuminate/Database/Migrations/stubs');
    }

    public function setIlluminateCustomStubPath(): void
    {
        if ($this->customStubPath === $this->arangoStubPath) {
            $this->customStubPath = $this->getDefaultIlluminateCustomStubPath();
        }
    }

    public function setArangoCustomStubPath(): void
    {
        if ($this->customStubPath === $this->getDefaultIlluminateCustomStubPath()) {
            $this->customStubPath = __DIR__ . '/stubs';
        }
    }
}
