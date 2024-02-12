<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Console\Concerns;

trait ArangoCommands
{
    protected bool $useArangoDB = false;


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
