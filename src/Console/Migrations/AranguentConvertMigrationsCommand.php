<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Database\Migrations\Migrator;

class AranguentConvertMigrationsCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aranguent:convert-migrations
                {--path= : The path to the migrations files to be converted}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert existing migrations to Aranguent migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration command instance.
     *
     *
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        // Next, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that migrations may be run for any path within the applications.
        $files = $this->migrator->getMigrationFiles($this->getMigrationPaths());
        foreach ($files as $file) {
            $this->convertMigrationFile($file);
        }
    }

    public function convertMigrationFile($filePath)
    {
        // Check for additional information to make sure we
        // partial matches aren't accidentally replaced.
        $replacements = [
            'Illuminate\Support\Facades\Schema' => 'LaravelFreelancerNL\Aranguent\Facades\Schema',
            'Illuminate\Database\Schema\Blueprint' => 'LaravelFreelancerNL\Aranguent\Schema\Blueprint',
        ];

        $content = file_get_contents($filePath);
        $content = str_replace(array_keys($replacements), $replacements, $content);
        file_put_contents($filePath, $content);
    }
}
