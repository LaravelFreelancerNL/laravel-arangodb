<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;
use LaravelFreelancerNL\FluentAQL\Grammar as FluentAqlGrammar;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as FluentAQL;

/*
 * Provides AQL syntax functions
 */

class Grammar extends FluentAqlGrammar
{
    use Macroable;

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
        '=' => '==',
        '<>' => '!=',
        '<=>' => '==',
        'rlike' => '=~',
        'not rlike' => '!~',
        'regexp' => '=~',
        'not regexp' => '!~',
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
     * @param Builder $builder
     * @param $table
     * @param string $postfix
     * @return mixed
     */
    protected function generateTableAlias($builder, $table, $postfix = 'Doc')
    {
        $builder->registerAlias($table, Str::singular($table).$postfix);

        return $builder;
    }

    protected function prefixTable($table)
    {
        return $this->tablePrefix.$table;
    }

    /**
     * Compile an insert statement into AQL.
     *
     * @param Builder $builder
     * @param array $values
     * @return Builder
     * @throws BindException
     */
    public function compileInsert(Builder $builder, array $values)
    {
        if (! is_array(reset($values))) {
            $values = [$values];
        }

        $table = $this->prefixTable($builder->from);

        if (empty($values)) {
            $builder->aqb = $builder->aqb->insert('{}', $table)->get();

            return $builder;
        }

        $builder->aqb = $builder->aqb->let('docs', $builder->aqb->bind($values))
            ->for('doc', 'docs')
            ->insert('doc', $table)
            ->return('NEW._key')
            ->get();

        return $builder;
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param Builder $builder
     * @param array $values
     * @return Builder
     * @throws BindException
     */
    public function compileInsertGetId(Builder $builder, $values)
    {
        return $this->compileInsert($builder, $values);
    }

    /**
     * Compile a select query into AQL.
     *
     * @param  Builder  $builder
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
     * @return Builder
     */
    protected function compileComponents(Builder $builder)
    {
        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.

            if (isset($builder->$component) && ! is_null($builder->$component)) {
                $method = 'compile'.ucfirst($component);

                $builder = $this->$method($builder, $builder->$component);
            }
        }

        return $builder;
    }

    /**
     * Compile the "from" portion of the query -> FOR in AQL.
     *
     * @param Builder $builder
     * @param string $table
     * @return Builder
     */
    protected function compileFrom(Builder $builder, $table)
    {
        $table = $this->prefixTable($table);
        $builder = $this->generateTableAlias($builder, $table);
        $tableAlias = $builder->getAlias($table);

        $builder->aqb = $builder->aqb->for($tableAlias, $table);

        return $builder;
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param Builder $builder
     * @return Builder
     */
    protected function compileWheres(Builder $builder)
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        if (is_null($builder->wheres)) {
            return $builder;
        }

        if (count($predicates = $this->compileWheresToArray($builder)) > 0) {
            $builder->aqb = $builder->aqb->filter($predicates);

            return $builder;
        }

        return $builder;
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $builder
     * @return array
     */
    protected function compileWheresToArray($builder)
    {
        $result = collect($builder->wheres)->map(function ($where) use ($builder) {
            // ArangoDB uses a double '=' for comparison
            if ($where['operator'] == '=') {
                $where['operator'] = '==';
            }
            $where['operator'] = $this->translateOperator($where['operator']);

            //Prefix table alias on the column
            $where['column'] = $this->prefixAlias($builder, $builder->from, $where['column']);

            return [
                $where['column'],
                $where['operator'],
                $where['value'],
                $where['boolean'],
            ];
        })->all();

        return $result;
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  Builder  $builder
     * @param  array  $aggregate
     * @return Builder
     */
    protected function compileAggregate(Builder $builder, $aggregate)
    {
        $method = 'compile'.ucfirst($aggregate['function']);

        return $this->$method($builder, $aggregate);
    }

    /**
     * Compile AQL for count aggregate.
     * @param Builder $builder
     * @param $aggregate
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
     * @return Builder
     */
    protected function compileMax(Builder $builder, $aggregate)
    {
        $column = $this->prefixAlias($builder, $builder->from, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->max($column));

        return $builder;
    }

    /**
     * Compile AQL for min aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     * @return Builder
     */
    protected function compileMin(Builder $builder, $aggregate)
    {
        $column = $this->prefixAlias($builder, $builder->from, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->min($column));

        return $builder;
    }

    /**
     * Compile AQL for average aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     * @return Builder
     */
    protected function compileAvg(Builder $builder, $aggregate)
    {
        $column = $this->prefixAlias($builder, $builder->from, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->average($column));

        return $builder;
    }

    /**
     * Compile AQL for sum aggregate.
     *
     * @param Builder $builder
     * @param $aggregate
     * @return Builder
     */
    protected function compileSum(Builder $builder, $aggregate)
    {
        $column = $this->prefixAlias($builder, $builder->from, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->sum($column));

        return $builder;
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param Builder $builder
     * @param array $orders
     * @return Builder
     */
    protected function compileOrders(Builder $builder, $orders)
    {
        if (! empty($orders)) {
            $builder->aqb = $builder->aqb->sort($this->compileOrdersToArray($builder, $orders));

            return $builder;
        }

        return $builder;
    }

    /**
     * Compile the query orders to an array.
     *
     * @param  Builder  $builder
     * @param  array  $orders
     * @return array
     */
    protected function compileOrdersToArray(Builder $builder, $orders)
    {
        return array_map(function ($order) {
            return $order['sql'] ?? $this->prefixTable($order['column']).' '.$order['direction'];
        }, $orders);
    }

    /**
     * Compile the "offset" portions of the query.
     * We are handling this first by saving the offset which will be used by the FluentAQL's limit function.
     *
     * @param Builder $builder
     * @param int $offset
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
     * @param int $limit
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
     * @param array $columns
     * @return Builder
     */
    protected function compileColumns(Builder $builder, array $columns) : Builder
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
//        if (! is_null($builder->aggregate)) {
//            return;
//        }

        $values = [];

        $doc = $builder->getAlias($builder->from);
        foreach ($columns as $column) {
            if ($column != null && $column != '*') {
                $values[$column] = $doc.'.'.$column;
            }
        }
        if ($builder->aggregate !== null) {
            $values = 'aggregateResult';
        }
        if (empty($values)) {
            $values = $doc;
        }

        $builder->aqb = $builder->aqb->return($values, (bool) $builder->distinct);

        return $builder;
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param Builder $builder
     * @param array $values
     * @return Builder
     */
    public function compileUpdate(Builder $builder, array $values)
    {
        $table = $this->prefixTable($builder->from);
        $builder = $this->generateTableAlias($builder, $table);
        $tableAlias = $builder->getAlias($table);
        $builder->aqb = $builder->aqb->for($tableAlias, $table);

        //Fixme: joins?
        $builder = $this->compileWheres($builder);

        $builder->aqb = $builder->aqb->update($tableAlias, $values, $table)->get();

        return $builder;
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param Builder $builder
     * @param null $_key
     * @return Builder
     */
    public function compileDelete(Builder $builder, $_key = null)
    {
        $table = $this->prefixTable($builder->from);
        $builder = $this->generateTableAlias($builder, $table);
        $tableAlias = $builder->getAlias($table);

        if (! is_null($_key)) {
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
     * @return string
     */
    public function compileRandom(Builder $builder)
    {
        return (string) $builder->aqb->rand();
    }

    /**
     * Translate sql operators to their AQL equivalent where possible.
     *
     * @param string $operator
     * @return mixed|string
     */
    private function translateOperator(string $operator)
    {
        if (isset($this->operatorTranslations[strtolower($operator)])) {
            $operator = $this->operatorTranslations[$operator];
        }

        return $operator;
    }

    /**
     * @param Builder $builder
     * @param string $target
     * @param string $value
     * @return Builder
     */
    protected function prefixAlias(Builder $builder, string $target, string $value) : string
    {
        return $builder->getAlias($target).'.'.$value;
    }
}
