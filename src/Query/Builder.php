<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsGroups;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsSearches;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsInserts;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsJoins;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsSubqueries;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsUpdates;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsWheres;
use LaravelFreelancerNL\Aranguent\Query\Concerns\ConvertsIdToKey;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesAliases;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesBindings;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as AQB;
use phpDocumentor\Reflection\Types\Boolean;

class Builder extends IlluminateQueryBuilder
{
    use BuildsGroups;
    use BuildsInserts;
    use BuildsJoins;
    use BuildsSearches;
    use BuildsSubqueries;
    use BuildsUpdates;
    use BuildsWheres;
    use ConvertsIdToKey;
    use HandlesAliases;
    use HandlesBindings;

    public AQB $aqb;

    /**
     * The current query value bindings.
     *
     * @var array
     */
    public $bindings = [
        'variable' => [],
        'from' => [],
        'search' => [],
        'join' => [],
        // TODO: another set of variabes?
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'order' => [],
        'union' => [],
        'unionOrder' => [],
        'select' => [],
        'insert' => [],
        'update' => [],
        'upsert' => [],
    ];


    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var Grammar
     */
    public $grammar;

    /**
     * The current query value bindings.
     *
     * @var null|array{fields: array<string>, searchTokens: array<string>, analyzer: string|null}
     */
    public ?array $search = null;

    /**
     * The query variables that should be set.
     *
     * @var array<mixed>
     */
    public $variables = [];

    /**
     * ID of the query
     * Used as prefix for automatically generated bindings.
     *
     * @var int
     */
    protected $queryId = 1;

    /**
     * @override
     * Create a new query builder instance.
     */
    public function __construct(
        Connection $connection,
        Grammar $grammar = null,
        Processor $processor = null,
        AQB $aqb = null
    ) {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
        $this->processor = $processor ?: $connection->getPostProcessor();
        if (!$aqb instanceof AQB) {
            $aqb = new AQB();
        }
        $this->aqb = $aqb;

        $this->queryId = spl_object_id($this);
    }

    public function getQueryId()
    {
        return $this->queryId;
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
    public function getBindings()
    {
        $extractedBindings = [];
        foreach ($this->bindings as $typeBinds) {
            foreach ($typeBinds as $key => $value) {
                $extractedBindings[$key] = $value;
            }
        }

        return $extractedBindings;
    }

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
        $this->registerTableAlias($table, $as);

        $this->from = $table;

        return $this;
    }

    /**
     * Run a pagination count query.
     *
     * @param array<mixed> $columns
     * @return array<mixed>
     */
    protected function runPaginationCountQuery($columns = ['*'])
    {
        $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];

        $closeResults = $this->cloneWithout($without)
            ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
            ->setAggregate('count', $this->withoutSelectAliases($columns))
            ->get()->all();

        return $closeResults;
    }

    /**
     * Set the columns to be selected.
     *
     * @param array<mixed>|mixed $columns
     */
    public function select($columns = ['*']): IlluminateQueryBuilder
    {
        $this->columns = [];
        $this->bindings['select'] = [];

        $columns = is_array($columns) ? $columns : func_get_args();

        foreach ($columns as $as => $column) {
            if (is_string($as) && $this->isQueryable($column)) {
                $this->selectSub($column, $as);
            } else {
                $this->addColumns([$as => $column]);
            }
        }

        return $this;
    }
    /**
     * Add a subselect expression to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|string  $query
     * @param  string  $as
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function selectSub($query, $as)
    {
        [$query, $bindings] = $this->createSub($query);

        $this->addColumns([$as => new Expression('('.$query.')')]);
        $this->registerTableAlias($as, $as);
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
                if (is_null($this->columns)) {
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
     * Add a union statement to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  bool  $all
     * @return $this
     */
    public function union($query, $all = false)
    {
        if ($query instanceof \Closure) {
            $query($query = $this->newQuery());
        }
        $this->importBindings($query);
        $this->unions[] = compact('query', 'all');

        return $this;
    }


    /**
     * Delete records from the database.
     *
     * @param mixed $id
     * @return int
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (!is_null($id)) {
            $this->where($this->from . '._key', '=', $id);
        }

        $this->applyBeforeQueryCallbacks();

        return $this->connection->delete(
            $this->grammar->compileDelete($this),
            $this->cleanBindings(
                $this->grammar->prepareBindingsForDelete($this->bindings)
            )
        );
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        $this->applyBeforeQueryCallbacks();

        $results = $this->connection->select(
            $this->grammar->compileExists($this),
            $this->getBindings(),
            !$this->useWritePdo
        );

        // If the results have rows, we will get the row and see if the exists column is a
        // boolean true. If there are no results for this query we will return false as
        // there are no rows for this query at all, and we can return that info here.
        if (isset($results[0])) {
            $results = (array) $results[0];

            return (bool) $results['exists'];
        }

        return false;
    }


    /**
     * Execute an aggregate function on the database.
     *
     * @param string $function
     * @param array<mixed> $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $results = $this->cloneWithout($this->unions ? [] : ['columns'])
            ->setAggregate($function, $columns)
            ->get($columns);


        if (!$results->isEmpty()) {
            return array_change_key_case((array)$results[0])['aggregate'];
        }

        return false;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|\Illuminate\Contracts\Database\Query\Expression|string $column
     * @param string $direction
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc')
    {
        if ($this->isQueryable($column)) {
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

    public function orderByRaw($sql, $bindings = [])
    {
        $type = 'Raw';

        $sql = new Expression($sql);

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
        // ArangoDB's random function doesn't accept a seed.
        unset($seed);

        return $this->orderByRaw($this->grammar->compileRandom());
    }


    /**
     * Set a variable
     */
    public function set(string $variable, IlluminateQueryBuilder|Expression|array|Boolean|Int|Float|String $value): Builder
    {
        if ($value instanceof Expression) {
            $this->variables[$variable] = $value->getValue($this->grammar);

            return $this;
        }

        if ($value instanceof IlluminateQueryBuilder) {
            $value->grammar->registerTableAlias($this->from);
            $value->grammar->compileSelect($value);

            $subquery = '(' . $value->toSql() . ')';

            // ArangoDB always returns an array of results. SQL will return a singular result
            // To mimic the same behaviour we take the first result.
            if ($value->hasLimitOfOne($value)) {
                $subquery = 'FIRST(' . $subquery . ')';
            }

            $this->bindings = array_merge(
                $this->bindings,
                $value->bindings
            );

            $this->variables[$variable] = $subquery;

            return $this;

        }
        $this->variables[$variable] = $this->bindValue($value, 'variable');

        return $this;
    }


    /**
     * Create a new query instance for sub-query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        $query = $this->newQuery();

        $query->importTableAliases($this);

        return $query;
    }

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the AQL representation of the query.
     */
    public function toAql(): string
    {
        return $this->toSql();
    }
}
