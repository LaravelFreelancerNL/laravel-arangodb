<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console\Concerns;

trait ArangoCommands
{
    public bool $useArangoDB = false;

    protected function connectionHasArangodbDriver(?string $name): bool
    {
        if ($name == '' && ! $this->arangodbIsDefaultConnection()) {
            return false;
        }
        if ($name == '' && $this->arangodbIsDefaultConnection()) {
            return true;
        }

        $connections = config('database.connections');

        return $connections[$name]['driver'] === 'arangodb';
    }

    protected function arangodbIsDefaultConnection(): bool
    {
        $connection = \DB::connection();

        return $connection->getDriverName() === 'arangodb';
    }


    protected function useFallback(): bool
    {
        return !$this->arangodbIsDefaultConnection() && !$this->useArangoDB;
    }
}
