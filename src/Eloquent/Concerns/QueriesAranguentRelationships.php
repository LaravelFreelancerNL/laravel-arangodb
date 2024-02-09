<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as IlluminateEloquentBuilder;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;

trait QueriesAranguentRelationships
{
    /**
     * @param mixed $function
     * @param IlluminateQueryBuilder $query
     * @param string $alias
     */
    public function handleAggregateFunction(IlluminateQueryBuilder $query, mixed $function, string $alias): void
    {
        if ($function === null) {
            $query->limit(1);

            return;
        }


        if ($function === 'exists') {
            [$subquery] = $this->getQuery()->createSub($query);

            $expression = new Expression(sprintf('(COUNT(%s)) > 0 ? true : false ', $subquery));

            $this->getQuery()->set(
                $alias,
                $expression,
                'postIterationVariables'
            )
                ->addSelect($alias);

            return;
        }


        [$subquery] = $this->getQuery()->createSub($query);

        $this->getQuery()->set(
            $alias,
            new Expression(strtoupper($function) . '(' . $subquery . ')'),
            'postIterationVariables'
        );

        $this->addSelect($alias);
    }

    /**
     * @param array<string> $segments
     * @param string $name
     * @return array<int, string|null>
     */
    public function extractNameAndAlias(array $segments, string $name): array
    {
        $alias = null;

        if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
            [$name, $alias] = [$segments[0], $segments[2]];
        }
        return [$name, $alias];
    }

    /**
     * Add a sub-query count clause to this query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return self
     */
    protected function addWhereCountQuery(IlluminateQueryBuilder $query, $operator = '>=', $count = 1, $boolean = 'and')
    {
        [$subquery] = $this->getQuery()->createSub($query);

        return $this->where(
            new Expression('LENGTH(' . $subquery . ')'),
            $operator,
            new Expression($count),
            $boolean
        );
    }

    /**
     * Merge the where constraints from another query to the current query.
     *
     * @param IlluminateEloquentBuilder $from
     * @return IlluminateEloquentBuilder|static
     */
    public function mergeConstraintsFrom(Builder $from)
    {
        $whereBindings = $this->getQuery()->getBindings();

        $wheres = $from->getQuery()->from !== $this->getQuery()->from
            ? $this->requalifyWhereTables(
                $from->getQuery()->wheres,
                (string) $from->getQuery()->grammar->getValue($from->getQuery()->from),
                $this->getModel()->getTable()
            ) : $from->getQuery()->wheres;

        // Here we have some other query that we want to merge the where constraints from. We will
        // copy over any where constraints on the query as well as remove any global scopes the
        // query might have removed. Then we will return ourselves with the finished merging.
        return $this->withoutGlobalScopes(
            $from->removedScopes()
        )->mergeWheres(
            $wheres,
            $whereBindings
        );
    }

    /**
     * Add subselect queries to include an aggregate value for a relationship.
     *
     * @param  mixed  $relations
     * @param  string  $column
     * @param  string  $function
     * @return $this
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function withAggregate($relations, $column, $function = null)
    {
        if (empty($relations)) {
            return $this;
        }

        if (empty($this->query->columns)) {
            $this->query->select([$this->query->from . '.*']);
        }

        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($this->parseWithRelations($relations) as $name => $constraints) {
            // First we will determine if the name has been aliased using an "as" clause on the name
            // and if it has we will extract the actual relationship name and the desired name of
            // the resulting column. This allows multiple aggregates on the same relationships.
            $segments = explode(' ', $name);

            [$name, $alias] = $this->extractNameAndAlias($segments, $name);

            $relation = $this->getRelationWithoutConstraints((string) $name);

            $expression = $column;

            if ($function) {
                $hashedColumn = $this->getRelationHashedColumn($column, $relation);

                $wrappedColumn = $this->getQuery()->getGrammar()->wrap(
                    $column === '*' ? $column : $relation->getRelated()->qualifyColumn($hashedColumn)
                );

                $expression = $function === 'exists' ? $wrappedColumn : sprintf('%s(%s)', $function, $wrappedColumn);
            }

            // Here, we will grab the relationship sub-query and prepare to add it to the main query
            // as a sub-select. First, we'll get the "has" query and use that to get the relation
            // sub-query. We'll format this relationship name and append this column if needed.
            $query = $relation->getRelationExistenceQuery(
                $relation->getRelated()->newQuery(),
                $this,
                new Expression($expression)
            )->setBindings([], 'select');

            $query->callScope($constraints);

            $query = $query->mergeConstraintsFrom($relation->getQuery())->toBase();

            // If the query contains certain elements like orderings / more than one column selected
            // then we will remove those elements from the query so that it will execute properly
            // when given to the database. Otherwise, we may receive SQL errors or poor syntax.
            unset($query->orders);
            $query->setBindings([], 'order');

            if (is_array($query->columns) && count($query->columns) > 1) {
                $query->columns = [$query->columns[0]];
                $query->bindings['select'] = [];
            }

            // Finally, we will make the proper column alias to the query and run this sub-select on
            // the query builder. Then, we will return the builder instance back to the developer
            // for further constraint chaining that needs to take place on the query as needed.
            $alias = Str::snake(
                (string) preg_replace('/[^[:alnum:][:space:]_]/u', '', "$name $function $column")
            );

            $this->handleAggregateFunction($query, $function, $alias);
        }

        return $this;
    }
}
