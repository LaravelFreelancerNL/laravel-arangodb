<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Exception;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar as IlluminateQueryGrammar;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsGroups;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsSearches;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsInserts;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsJoins;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsSelects;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsSubqueries;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsUpdates;
use LaravelFreelancerNL\Aranguent\Query\Concerns\BuildsWheres;
use LaravelFreelancerNL\Aranguent\Query\Concerns\ConvertsIdToKey;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesAliases;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesBindings;
use LaravelFreelancerNL\Aranguent\Query\Enums\VariablePosition;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as AQB;
use phpDocumentor\Reflection\Types\Boolean;

class Builder extends IlluminateQueryBuilder
{
    use BuildsGroups;
    use BuildsInserts;
    use BuildsJoins;
    use BuildsSearches;
    use BuildsSelects;
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
        'preIterationVariables' => [],
        'from' => [],
        'search' => [],
        'join' => [],
        'postIterationVariables' => [],
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
     * @var IlluminateQueryGrammar
     */
    public $grammar;

    /**
     * The current query value bindings.
     *
     * @var null|array{fields: mixed, searchText: mixed, analyzer: non-falsy-string}
     */
    public ?array $search = null;

    /**
     * The query variables that should be set before traversals (for/joins).
     *
     * @var array<mixed>
     */
    public $preIterationVariables = [];

    /**
     * The query variables that should be set after traversals (for/joins).
     *
     * @var array<mixed>
     */
    public $postIterationVariables = [];

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
        Connection             $connection,
        IlluminateQueryGrammar $grammar = null,
        Processor              $processor = null,
        AQB                    $aqb = null
    ) {
        parent::__construct($connection, $grammar, $processor);
//        $this->connection = $connection;
//        $this->grammar = $grammar ?: $connection->getQueryGrammar();
//        $this->processor = $processor ?: $connection->getPostProcessor();

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
            return ($results->first())->aggregate;
        }

        return false;
    }

    /**
     * Set a variable
     * @param string $variable
     * @param IlluminateQueryBuilder|Expression|array|Int|Float|String|Boolean $value
     * @param string|VariablePosition $variablePosition
     * @return Builder
     */
    public function set(
        string $variable,
        IlluminateQueryBuilder|Expression|array|Boolean|Int|Float|String $value,
        VariablePosition|string $variablePosition = VariablePosition::preIterations
    ): Builder {
        if (is_string($variablePosition)) {
            $variablePosition = VariablePosition::tryFrom($variablePosition) ?? VariablePosition::preIterations;
        }

        if ($value instanceof Expression) {
            $this->{$variablePosition->value}[$variable] = $value->getValue($this->grammar);

            return $this;
        }

        if ($value instanceof Builder) {

            [$subquery] = $this->createSub($value);

            $this->{$variablePosition->value}[$variable] = $subquery;

            return $this;
        }
        $this->{$variablePosition->value}[$variable] = $this->bindValue($value, $variablePosition->value);

        return $this;
    }

    public function isVariable(string $value): bool
    {
        if (
            key_exists($value, $this->preIterationVariables)
            ||  key_exists($value, $this->postIterationVariables)
        ) {
            return true;
        }

        return false;
    }

    public function isReference(mixed $value, array $variables = []): bool
    {
        if (!is_string($value) || empty($value)) {
            return false;
        }

        if (empty($variables)) {
            $variables = array_merge(
                array_keys($this->preIterationVariables),
                array_keys($this->postIterationVariables),
                $this->tableAliases,
            );
        }

        $variablesRegex = implode('|', $variables);

        return (bool) preg_match(
            '/^\`?('
            . $variablesRegex
            . '|CURRENT|NEW|OLD)\`?(\[\`.+\`\]|\[[\d\w\*]*\])*(\.(\`.+\`|@?[\d\w]*)(\[\`.+\`\]|\[[\d\w\*]*\])*)*$/',
            $value
        );
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
     * Prepend the database name if the given query is on another database.
     *
     * @param mixed $query
     * @return mixed
     * @throws Exception
     */
    protected function prependDatabaseNameIfCrossDatabaseQuery($query)
    {
        if ($query->getConnection()->getDatabaseName() !==
            $this->getConnection()->getDatabaseName()) {
            $databaseName = $query->getConnection()->getDatabaseName();

            if (!str_starts_with($query->from, $databaseName) && !str_contains($query->from, '.')) {
                throw new Exception(message: 'ArangoDB does not support cross database queries.');
            }
        }

        return $query;
    }

    /**
     * Get the AQL representation of the query.
     */
    public function toAql(): string
    {
        return $this->toSql();
    }
}
