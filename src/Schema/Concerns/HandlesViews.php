<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use ArangoClient\Exceptions\ArangoException;

trait HandlesViews
{
    /**
     * @param  array<mixed>  $properties
     *
     * @throws ArangoException
     */
    public function createView(string $name, array $properties, string $type = 'arangosearch')
    {
        $view = $properties;
        $view['name'] = $name;
        $view['type'] = $type;

        $this->schemaManager->createView($view);
    }

    /**
     * @throws ArangoException
     */
    public function getView(string $name): \stdClass
    {
        return $this->schemaManager->getView($name);
    }

    public function hasView($view)
    {
        return $this->handleExceptionsAsQueryExceptions(function () use ($view) {
            return $this->schemaManager->hasView($view);
        });
    }

    /**
     * @throws ArangoException
     */
    public function getAllViews(): array
    {
        return $this->schemaManager->getViews();
    }

    /**
     * @throws ArangoException
     */
    public function editView(string $name, array $properties)
    {
        $this->schemaManager->updateView($name, $properties);
    }

    /**
     * @throws ArangoException
     */
    public function renameView(string $from, string $to)
    {
        $this->schemaManager->renameView($from, $to);
    }

    /**
     * @throws ArangoException
     */
    public function dropView(string $name)
    {
        $this->schemaManager->deleteView($name);
    }

    /**
     * @throws ArangoException
     */
    public function dropViewIfExists(string $name): bool
    {
        if ($this->hasView($name)) {
            $this->schemaManager->deleteView($name);
        }

        return true;
    }

    /**
     * Drop all views from the schema.
     *
     * @throws ArangoException
     */
    public function dropAllViews(): void
    {
        $views = $this->schemaManager->getViews();

        foreach ($views as $view) {
            $this->schemaManager->deleteView($view->name);
        }
    }
}