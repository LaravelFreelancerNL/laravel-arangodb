<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder as IlluminateBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as ArangoQueryBuilder;

trait QueriesAranguentRelationships
{
    /**
     * Add a sub-query count clause to this query.
     *
     * @param  QueryBuilder  $query
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return IlluminateBuilder
     */
    protected function addWhereCountQuery(
        QueryBuilder $query,
        $operator = '>=',
        $count = 1,
        $boolean = 'and'
    ): IlluminateBuilder {

        $query->grammar->compileSelect($query);

        return $this->where(
            $query->aqb->count($query->aqb),
            $operator,
            is_numeric($count) ? new Expression($count) : $count,
            $boolean
        );
    }


    /**
     * Add subselect queries to include an aggregate value for a relationship.
     * Overrides method in QueriesRelationships trait
     *
     * @param  mixed  $relations
     * @param  string|null  $column
     * @param  string  $function
     * @return $this
     */
    public function withAggregate($relations, $column, $function = null)
    {
        $result = null;

        if (empty($relations)) {
            return $this;
        }

        if (is_null($this->query->columns)) {
            $this->query->select([$this->query->from . '.*']);
        }

        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($this->parseWithRelations($relations) as $name => $constraints) {
            // First we will determine if the name has been aliased using an "as" clause on the name
            // and if it has we will extract the actual relationship name and the desired name of
            // the resulting column. This allows multiple aggregates on the same relationships.
            $segments = explode(' ', $name);

            unset($alias);

            if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
                [$name, $alias] = [$segments[0], $segments[2]];
            }

            $relation = $this->getRelationWithoutConstraints($name);

            // Here, we will grab the relationship sub-query and prepare to add it to the main query
            // as a sub-select. First, we'll get the "has" query and use that to get the relation
            // sub-query. We'll format this relationship name and append this column if needed.
            $query = $relation->getRelationExistenceQuery(
                $relation->getRelated()->newQuery(),
                $this
            );

            $query->callScope($constraints);

            $query = $query->mergeConstraintsFrom($relation->getQuery())->toBase();

            // If the query contains certain elements like orderings / more than one column selected
            // then we will remove those elements from the query so that it will execute properly
            // when given to the database. Otherwise, we may receive SQL errors or poor syntax.
            $query->orders = null;

            if (count($query->columns) > 1) {
                $query->columns = [$query->columns[0]];
                $query->bindings['select'] = [];
            }

            $result = $this->getAggregateResultQuery($query, $function);

            // Finally, we will make the proper column alias to the query and run this sub-select on
            // the query builder. Then, we will return the builder instance back to the developer
            // for further constraint chaining that needs to take place on the query as needed.
            $alias = $alias ?? Str::snake(
                preg_replace('/[^[:alnum:][:space:]_]/u', '', "$name $function $column")
            );

            $this->selectSub(
                $result,
                $alias
            );
        }

        return $this;
    }

    /**
     * @param $query
     * @param $function
     * @return mixed
     */
    protected function getAggregateResultQuery($query, $function)
    {
        if ($function) {
            $query->grammar->compileSelect($query);
            $result = (new ArangoQueryBuilder())->$function($query->aqb);
        }
        if (! $function) {
            $query->limit(1);
            $result = $query;
        }

        return $result;
    }
}
