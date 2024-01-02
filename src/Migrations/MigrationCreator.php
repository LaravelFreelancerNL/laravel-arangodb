<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Migrations;

use Illuminate\Database\Migrations\MigrationCreator as IlluminateMigrationCreator;
use Illuminate\Filesystem\Filesystem;

class MigrationCreator extends IlluminateMigrationCreator
{
    /**
     * Create a new migration creator instance.
     *
     * @param  string  $customStubPath
     */
    public function __construct(Filesystem $files, $customStubPath)
    {
        $this->files = $files;
        $this->customStubPath = $customStubPath;
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__ . '/../../stubs';
    }
}
