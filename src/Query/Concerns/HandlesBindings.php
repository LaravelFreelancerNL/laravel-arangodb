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

    protected function bindValue($value, string $type = 'where')
    {
        assert($this->grammar instanceof Grammar);

        if ($this->grammar->isBind($value, $type)) {
            return $value;
        }
        if (!$value instanceof Expression) {
            $this->addBinding($value, $type);
            $value = $this->replaceValueWithBindVariable($type);
        }

        return $value;
    }

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * @param array $bindings
     * @return array
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

    protected function getLastBindVariable(string $type = 'where')
    {
        return array_key_last($this->bindings[$type]);
    }

    public function importBindings(IlluminateQueryBuilder $query, string $type = null): void
    {
        if ($type) {
            $this->bindings[$type] = array_merge($this->bindings[$type], $query->bindings[$type]);
            return;
        }
        $this->bindings = array_merge_recursive($this->bindings, $query->bindings);
    }


    protected function replaceValueWithBindVariable(string $type = 'where')
    {
        return '@' . $this->getLastBindVariable($type);
    }
}
