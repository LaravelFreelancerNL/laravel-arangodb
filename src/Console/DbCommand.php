<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console;

use Illuminate\Database\Console\DbCommand as IlluminateDbCommand;
use Symfony\Component\Process\Process;

class DbCommand extends IlluminateDbCommand
{
    /**
     * Execute the console command.
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle()
    {
        $connection = $this->getConnection();

        if ((! isset($connection['host']) && ! isset($connection['endpoint'])) && $connection['driver'] !== 'sqlite') {
            $this->components->error('No host specified for this database connection.');
            $this->line('  Use the <options=bold>[--read]</> and <options=bold>[--write]</> options to specify a read or write connection.');
            $this->newLine();

            return IlluminateDbCommand::FAILURE;
        }

        (new Process(
            array_merge([$this->getCommand($connection)], $this->commandArguments($connection)),
            null,
            $this->commandEnvironment($connection)
        ))->setTimeout(null)->setTty(true)->mustRun(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return 0;
    }

    /**
     * Get the database client command to run.
     *
     * @param  array<mixed>  $connection
     * @return string
     */
    public function getCommand(array $connection)
    {
        return [
            'mysql' => 'mysql',
            'pgsql' => 'psql',
            'sqlite' => 'sqlite3',
            'sqlsrv' => 'sqlcmd',
            'arangodb' => 'arangosh',
        ][$connection['driver']];
    }

    /**
     * Get the arguments for the ArangoDB CLI (Arangosh).
     *
     * @param  array<mixed>  $connection
     * @return array<mixed>
     */
    protected function getArangodbArguments(array $connection)
    {
        return array_merge([
            '--server.endpoint=' . $connection['endpoint'],
            '--server.database=' . $connection['database'],
            '--server.username=' . $connection['username'],
        ], $this->getOptionalArguments([
            'password' => '--server.password=' . $connection['password'],
        ], $connection));
    }

}
