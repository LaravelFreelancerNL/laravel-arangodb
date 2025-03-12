<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use ArangoClient\Exceptions\ArangoException;

trait HandlesGraphs
{
    /**
     * @param  array<mixed>  $properties
     * @throws ArangoException
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function createGraph(string $name, array $properties = [], bool $waitForSync = false)
    {
        $this->schemaManager->createGraph($name, $properties, $waitForSync);
    }

    public function hasGraph(string $name): bool
    {
        return $this->handleExceptionsAsQueryExceptions(function () use ($name) {
            return $this->schemaManager->hasGraph($name);
        });
    }

    /**
     * @param string $name
     * @return mixed[]
     * @throws ArangoException
     */
    public function getGraph(string $name): array
    {
        return (array) $this->schemaManager->getGraph($name);
    }

    /**
     * @return mixed[]
     * @throws ArangoException
     */
    public function getGraphs(): array
    {
        return $this->mapResultsToArray(
            $this->schemaManager->getGraphs(),
        );
    }

    /**
     * @throws ArangoException
     */
    public function dropGraph(string $name)
    {
        $this->schemaManager->deleteGraph($name);
    }

    /**
     * @throws ArangoException
     */
    public function dropGraphIfExists(string $name): bool
    {
        if ($this->hasGraph($name)) {
            $this->schemaManager->deleteGraph($name);
        }

        return true;
    }

    public function dropAllGraphs(): void
    {
        $this->schemaManager->deleteAllGraphs();
    }
}
