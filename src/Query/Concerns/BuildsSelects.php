<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder as IlluminateEloquentBuilder;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Query\Grammar;

trait BuildsSelects
{
    /**
     * Set the table which the query is targeting.
     *
     * @param \Closure|IlluminateQueryBuilder|string $table
     * @param string|null $as
     * @return IlluminateQueryBuilder
     */
    public function from($table, $as = null)
    {
        if ($this->isQueryable($table)) {
            return $this->fromSub($table, $as);
        }

        assert(is_string($table));

        $this->registerTableAlias($table, $as);

        $this->from = $table;

        return $this;
    }

    /**
     * Set the columns to be selected.
     *
     * @param array<mixed>|mixed $columns
     * @return IlluminateQueryBuilder
     * @throws Exception
     */
    public function select($columns = ['*']): IlluminateQueryBuilder
    {
        $this->columns = [];
        $this->bindings['select'] = [];

        $columns = is_array($columns) ? $columns : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                $this->selectSub($column, $as);
                continue;
            }

            $this->addColumns([$as => $column]);
        }

        return $this;
    }

    /**
     * Add a subselect expression to the query.
     *
     * @param \Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder|string $query
     * @param string $as
     * @return $this
     *
     * @throws \InvalidArgumentException
     * @throws Exception
     */
    public function selectSub($query, $as)
    {
        [$query] = $this->createSub($query, true);

        $this->set($as, new Expression($query), 'postIterationVariables');

        $this->addColumns([$as]);

        return $this;
    }

    /**
     * Add a new select column to the query.
     *
     * @param array|mixed $column
     * @return $this
     */
    public function addSelect($column)
    {
        $columns = is_array($column) ? $column : func_get_args();

        $this->addColumns($columns);

        return $this;
    }

    /**
     * @param array<mixed> $columns
     */
    protected function addColumns(array $columns): void
    {
        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                if (empty($this->columns)) {
                    $this->select($this->from . '.*');
                }

                $this->selectSub($column, $as);

                continue;
            }

            if (is_string($as)) {
                $this->columns[$as] = $column;

                continue;
            }

            $this->columns[] = $column;
        }
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param \Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder|Expression|string $column
     * @param string $direction
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc')
    {
        if ($this->isQueryable($column)) {
            assert(!$column instanceof Expression);
            [$query, $bindings] = $this->createSub($column);

            $column = new Expression('(' . $query . ')');

            $this->addBinding($bindings, $this->unions ? 'unionOrder' : 'order');
        }

        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * @param string $aql
     * @param array<mixed> $bindings
     * @return $this
     */
    public function orderByRaw($aql, $bindings = [])
    {
        $type = 'Raw';

        $sql = new Expression($aql);

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = compact('type', 'sql');

        if (!isset($this->bindings[$this->unions ? 'unionOrders' : 'orders'])) {
            $this->bindings[$this->unions ? 'unionOrders' : 'orders'] = $bindings;

            return $this;
        }

        $this->bindings[$this->unions ? 'unionOrders' : 'orders'] = array_merge(
            $this->bindings[$this->unions ? 'unionOrders' : 'orders'],
            $bindings
        );

        return $this;
    }

    /**
     * Put the query's results in random order.
     *
     * @param string $seed
     * @return $this
     */
    public function inRandomOrder($seed = '')
    {
        assert($this->grammar instanceof Grammar);

        // ArangoDB's random function doesn't accept a seed.
        unset($seed);

        return $this->orderByRaw($this->grammar->compileRandom());
    }

    /**
     * Add a union statement to the query.
     *
     * @param  \Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder $query
     * @param  bool  $all
     * @return $this
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function union($query, $all = false)
    {
        if ($query instanceof \Closure) {
            $query($query = $this->newQuery());
        }

        if ($query instanceof IlluminateEloquentBuilder) {
            $query = $query->getQuery();
        }

        $this->importBindings($query);
        $this->unions[] = compact('query', 'all');

        return $this;
    }
}
