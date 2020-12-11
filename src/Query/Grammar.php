<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesWhereClauses;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HasAliases;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;
use LaravelFreelancerNL\FluentAQL\Expressions\FunctionExpression;
use LaravelFreelancerNL\FluentAQL\Grammar as FluentAqlGrammar;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

/*
 * Provides AQL syntax functions
 */

class Grammar extends FluentAqlGrammar
{
    use CompilesWhereClauses;
    use HasAliases;
    use Macroable;

    public $name;

    /**
     * The grammar table prefix.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The grammar table prefix.
     *
     * @var null|int
     */
    protected $offset = null;

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'from',
        'joins',
        'wheres',
        'groups',
        'aggregate',
        'havings',
        'orders',
        'offset',
        'limit',
        'columns',
    ];

    protected $operatorTranslations = [
        '='          => '==',
        '<>'         => '!=',
        '<=>'        => '==',
        'rlike'      => '=~',
        'not rlike'  => '!~',
        'regexp'     => '=~',
        'not regexp' => '!~',
    ];

    protected $whereTypeOperators = [
        'In'    => 'IN',
        'NotIn' => 'NOT IN',
    ];

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d\TH:i:s.v\Z';
    }

    /**
     * Get the grammar specific operators.
     *
     * @return array
     */
    public function getOperators()
    {
        return $this->comparisonOperators;
    }

    protected function prefixTable($table)
    {
        return $this->tablePrefix . $table;
    }

    /**
     * Compile an insert statement into AQL.
     *
     * @param Builder $builder
     * @param array   $values
     *
     * @throws BindException
     *
     * @return Builder
     */
    public function compileInsert(Builder $builder, array $values)
    {
        if (Arr::isAssoc($values)) {
            $values = [$values];
        }
        $table = $this->prefixTable($builder->from);

        if (empty($values)) {
            $builder->aqb = $builder->aqb->insert('{}', $table)->get();

            return $builder;
        }

        $builder->aqb = $builder->aqb->let('values', $values)
            ->for('value', 'values')
            ->insert('value', $table)
            ->return('NEW._key')
            ->get();

        return $builder;
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param Builder $builder
     * @param array   $values
     *
     * @throws BindException
     *
     * @return Builder
     */
    public function compileInsertGetId(Builder $builder, $values)
    {
        return $this->compileInsert($builder, $values);
    }

    /**
     * Compile a select query into AQL.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function compileSelect(Builder $builder)
    {
//        if ($builder->unions && $builder->aggregate) {
//            return $this->compileUnionAggregate($builder);
//        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.

        $builder = $this->compileComponents($builder);

//        if ($builder->unions) {
//            $sql = $this->wrapUnion($sql).' '.$this->compileUnions($builder);
//        }

        $builder->aqb = $builder->aqb->get();

        return $builder;
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    protected function compileComponents(Builder $builder)
    {
        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.

            if (isset($builder->$component) && !is_null($builder->$component)) {
                $method = 'compile' . ucfirst($component);

                $builder = $this->$method($builder, $builder->$component);
            }
        }

        return $builder;
    }

    /**
     * Compile the "from" portion of the query -> FOR in AQL.
     *
     * @param Builder $builder
     * @param string  $table
     *
     * @return Builder
     */
    protected function compileFrom(Builder $builder, $table)
    {
        $table = $this->prefixTable($table);
        $alias = $this->registerTableAlias($table);

        $builder->aqb = $builder->aqb->for($alias, $table);

        return $builder;
    }

    /**
     * Compile the "join" portions of the query.
     *
     * @param Builder $builder
     * @param array   $joins
     *
     * @return string
     */
    protected function compileJoins(Builder $builder, $joins)
    {
        foreach ($joins as $join) {
            $compileMethod = 'compile' . ucfirst($join->type) . 'Join';
            $builder = $this->$compileMethod($builder, $join);
        }

        return $builder;
    }

    protected function compileInnerJoin(Builder $builder, $join)
    {
        $table = $join->table;
        $alias = $this->generateTableAlias($table);
        $this->registerTableAlias($table, $alias);
        $builder->aqb = $builder->aqb->for($alias, $table)
            ->filter($this->compileWheresToArray($join));

        return $builder;
    }

    protected function compileLeftJoin(Builder $builder, $join)
    {
        $table = $join->table;
        $alias = $this->generateTableAlias($table);
        $this->registerTableAlias($table, $alias);

        $resultsToJoin = (new QueryBuilder())
            ->for($alias, $table)
            ->filter($this->compileWheresToArray($join))
            ->return($alias);

        $builder->aqb = $builder->aqb->let($table, $resultsToJoin)
            ->for(
                $alias,
                $builder->aqb->if(
                    [$builder->aqb->length($table), '>', 0],
                    $table,
                    '[]'
                )
            );

        return $builder;
    }

    //FOR user IN users
    //  LET friends = (
//    FOR friend IN friends
//      FILTER friend.user == user._key
//      RETURN friend
    //  )
    //  FOR friendToJoin IN (
//    LENGTH(friends) > 0 ? friends :
//      [ { /* no match exists */ } ]
//    )
//    RETURN {
//      user: user,
//      friend: friend
//    }

    protected function compileCrossJoin(Builder $builder, $join)
    {
        $table = $join->table;
        $alias = $this->generateTableAlias($table);
        $builder->aqb = $builder->aqb->for($alias, $table);

        return $builder;
    }


    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $builder
     * @param array   $aggregate
     *
     * @return Builder
     */
    protected function compileAggregate(Builder $builder, $aggregate)
    {
        $method = 'compile' . ucfirst($aggregate['function']);

        return $this->$method($builder, $aggregate);
    }

    /**
     * Compile AQL for count aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileCount(Builder $builder, $aggregate)
    {
        $builder->aqb = $builder->aqb->collect()->withCount('aggregateResult');

        return $builder;
    }

    /**
     * Compile AQL for max aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileMax(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->max($column));

        return $builder;
    }

    /**
     * Compile AQL for min aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileMin(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->min($column));

        return $builder;
    }

    /**
     * Compile AQL for average aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileAvg(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->average($column));

        return $builder;
    }

    /**
     * Compile AQL for sum aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     *
     * @return Builder
     */
    protected function compileSum(Builder $builder, $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->sum($column));

        return $builder;
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param Builder $builder
     * @param array   $orders
     *
     * @return Builder
     */
    protected function compileOrders(Builder $builder, $orders)
    {
        if (!empty($orders)) {
            $orders = $this->compileOrdersToFlatArray($builder, $orders);
            $builder->aqb = $builder->aqb->sort(...$orders);

            return $builder;
        }

        return $builder;
    }

    /**
     * Compile the query orders to an array.
     *
     * @param Builder $builder
     * @param array   $orders
     *
     * @return array
     */
    protected function compileOrdersToFlatArray(Builder $builder, $orders)
    {
        $flatOrders = [];

        foreach ($orders as $order) {
            if (!isset($order['type']) || $order['type'] != 'Raw') {
                $order['column'] = $this->normalizeColumn($builder, $order['column']);
            }

            $flatOrders[] = $order['column'];

            if (isset($order['direction'])) {
                $flatOrders[] = $order['direction'];
            }
        }

        return $flatOrders;
    }

    /**
     * Compile the "offset" portions of the query.
     * We are handling this first by saving the offset which will be used by the FluentAQL's limit function.
     *
     * @param Builder $builder
     * @param int     $offset
     *
     * @return Builder
     */
    protected function compileOffset(Builder $builder, $offset)
    {
        $this->offset = (int) $offset;

        return $builder;
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param Builder $builder
     * @param int     $limit
     *
     * @return Builder
     */
    protected function compileLimit(Builder $builder, $limit)
    {
        if ($this->offset !== null) {
            $builder->aqb = $builder->aqb->limit((int) $this->offset, (int) $limit);

            return $builder;
        }
        $builder->aqb = $builder->aqb->limit((int) $limit);

        return $builder;
    }

    /**
     * Compile the "RETURN" portion of the query.
     *
     * @param Builder $builder
     * @param array   $columns
     *
     * @return Builder
     */
    protected function compileColumns(Builder $builder, array $columns): Builder
    {
        $returnDocs = [];
        $returnAttributes = [];
        $values = [];

        foreach ($columns as $column) {
            // Extract rows
            if (substr($column, strlen($column) - 2)  === '.*') {
                $table = substr($column, 0, strlen($column) - 2);
                $returnDocs[] = $this->getTableAlias($table);

                continue;
            }

            if ($column != null && $column != '*') {
                [$column, $alias] = $this->extractAlias($column);

                $returnAttributes[$alias] = $this->normalizeColumn($builder, $column);
            }
        }
        if (! empty($returnAttributes) && empty($returnDocs)) {
            $values = $returnAttributes;
        }
        if (! empty($returnAttributes) && ! empty($returnDocs)) {
            $returnDocs[] = $returnAttributes;
        }
        if (! empty($returnDocs)) {
            $values = $builder->aqb->merge(...$returnDocs);
        }

        if ($builder->aggregate !== null) {
            $values = ['aggregate' => 'aggregateResult'];
        }

        if (empty($values)) {
            $values = $this->getTableAlias($builder->from);
            if (is_array($builder->joins) && !empty($builder->joins)) {
                $values = $this->mergeJoinResults($builder, $values);
            }
        }

        $builder->aqb = $builder->aqb->return($values, (bool) $builder->distinct);

        return $builder;
    }

    protected function mergeJoinResults($builder, $baseTable)
    {
        $tablesToJoin = [];
        foreach ($builder->joins as $join) {
            $tablesToJoin[] = $this->getTableAlias($join->table);
        }
        $tablesToJoin = array_reverse($tablesToJoin);
        $tablesToJoin[] = $baseTable;

        return $builder->aqb->merge(...$tablesToJoin);
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param Builder $builder
     * @param array   $values
     *
     * @return Builder
     */
    public function compileUpdate(Builder $builder, array $values)
    {

        $table = $this->prefixTable($builder->from);
        $tableAlias = $this->generateTableAlias($table);

        $builder->aqb = $builder->aqb->for($tableAlias, $table);

        //Fixme: joins?
        $builder = $this->compileWheres($builder);

        $builder->aqb = $builder->aqb->update($tableAlias, $values, $table)->get();

        return $builder;
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @SuppressWarnings(PHPMD.CamelCaseParameterName)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     *
     * Fixme: use Laravel default parameter name
     *
     * @param Builder $builder
     * @param null    $_key
     *
     * @return Builder
     */
    public function compileDelete(Builder $builder, $_key = null)
    {
        $table = $this->prefixTable($builder->from);
        $tableAlias = $this->generateTableAlias($table);


        if (!is_null($_key)) {
            $builder->aqb = $builder->aqb->remove((string) $_key, $table)->get();

            return $builder;
        }

        $builder->aqb = $builder->aqb->for($tableAlias, $table);

        //Fixme: joins?
        $builder = $this->compileWheres($builder);

        $builder->aqb = $builder->aqb->remove($tableAlias, $table)->get();

        return $builder;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param Builder $builder
     *
     * @return FunctionExpression;
     */
    public function compileRandom(Builder $builder)
    {
        return $builder->aqb->rand();
    }

    /**
     * Translate sql operators to their AQL equivalent where possible.
     *
     * @param string $operator
     *
     * @return mixed|string
     */
    private function translateOperator(string $operator)
    {
        if (isset($this->operatorTranslations[strtolower($operator)])) {
            $operator = $this->operatorTranslations[$operator];
        }

        return $operator;
    }
}
