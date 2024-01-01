<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Query\Grammar;

trait HandlesBindings
{
    /**
     * Add a binding to the query.
     *
     * @param mixed $value
     * @param string $type
     * @return IlluminateQueryBuilder
     *
     * @throws \InvalidArgumentException
     */
    public function addBinding($value, $type = 'where')
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        $bindVariable = $this->generateBindVariable($type);
        $this->bindings[$type][$bindVariable] = $this->castBinding($value);

        return $this;
    }

    protected function bindValue(mixed $value, string $type = 'where'): mixed
    {
        assert($this->grammar instanceof Grammar);

        if ($this->grammar->isBind($value)) {
            return $value;
        }
        if (!$value instanceof Expression && !$this->isReference($value)) {
            $this->addBinding($value, $type);
            $value = $this->replaceValueWithBindVariable($type);
        }

        return $value;
    }

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * @param array<mixed> $bindings
     * @return array<mixed>
     */
    public function cleanBindings(array $bindings)
    {
        return collect($bindings)
            ->reject(function ($binding) {
                return $binding instanceof Expression;
            })
            ->map([$this, 'castBinding'])
            ->all();
    }

    protected function generateBindVariable(string $type = 'where'): string
    {
        return $this->queryId . '_' . $type . '_' . (count($this->bindings[$type]) + 1);
    }

    protected function getLastBindVariable(string $type = 'where'): string
    {
        return array_key_last($this->bindings[$type]);
    }

    /**
     * @param IlluminateQueryBuilder $query
     * @param string|null $type
     * @return void
     */
    public function importBindings($query, string $type = null): void
    {
        if ($type) {
            $this->bindings[$type] = array_merge($this->bindings[$type], $query->bindings[$type]);
            return;
        }
        $this->bindings = array_merge_recursive($this->bindings, $query->bindings);
    }

    /**
     * Set the bindings on the query builder.
     *
     * @param  array<mixed>  $bindings
     * @param  string  $type
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        if (empty($bindings)) {
            return $this;
        }

        $this->bindings[$type] = array_merge($this->bindings[$type], $bindings);

        return $this;
    }

    protected function replaceValueWithBindVariable(string $type = 'where'): string
    {
        return '@' . $this->getLastBindVariable($type);
    }
}
