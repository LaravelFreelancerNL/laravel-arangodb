<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use Closure;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;

trait UsesBlueprints
{
    /**
     * Create a new command set with a Closure.
     *
     * @param  string  $collection
     * @return Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $this->grammar, $this->schemaManager, $callback, $prefix);
        }

        return new Blueprint($table, $this->grammar, $this->schemaManager, $callback, $prefix);
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     *
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    protected function build($blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Create a new collection on the schema.
     *
     * @param  array<mixed>  $config
     */
    public function create($table, Closure $callback, array $config = []): void
    {
        $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($callback, $config) {
            $blueprint->create($config);

            $callback($blueprint);
        }));
    }

    /**
     * Modify a table's schema.
     *
     * @param  string  $table
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->build($this->createBlueprint($table, $callback));
    }
}
