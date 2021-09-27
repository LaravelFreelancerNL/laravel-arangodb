<?php

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesAggregates;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesColumns;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesGroups;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesJoins;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesWhereClauses;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HasAliases;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;
use LaravelFreelancerNL\FluentAQL\Expressions\FunctionExpression;
use LaravelFreelancerNL\FluentAQL\Grammar as FluentAqlGrammar;

/*
 * Provides AQL syntax functions
 */

class Grammar extends FluentAqlGrammar
{
    use CompilesAggregates;
    use CompilesColumns;
    use CompilesJoins;
    use CompilesGroups;
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
        'variables',
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
            $builder->aqb = $builder->aqb->insert('{}', $table);

            return $builder;
        }

        $builder->aqb = $builder->aqb->let('values', $values)
            ->for('value', 'values')
            ->insert('value', $table)
            ->return('NEW._id');

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
     * @param  Builder  $builder
     * @param  array $variables
     * @return Builder
     */
    protected function compileVariables(Builder $builder, array $variables)
    {
        if (! empty($variables)) {
            foreach ($variables as $variable => $data) {
                $builder->aqb = $builder->aqb->let($variable, $data);
            }
        }

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

        $builder->aqb = $builder->aqb->update($tableAlias, $values, $table);

        return $builder;
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @SuppressWarnings(PHPMD.CamelCaseParameterName)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     *
     * @param Builder $builder
     * @param null    $id
     *
     * @return Builder
     */
    public function compileDelete(Builder $builder, $id = null)
    {
        $table = $this->prefixTable($builder->from);
        $tableAlias = $this->generateTableAlias($table);


        if (!is_null($id)) {
            $builder->aqb = $builder->aqb->remove((string) $id, $table);

            return $builder;
        }

        $builder->aqb = $builder->aqb->for($tableAlias, $table);

        //Fixme: joins?
        $builder = $this->compileWheres($builder);

        $builder->aqb = $builder->aqb->remove($tableAlias, $table);

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
     * Get the value of a raw expression.
     *
     * @param  \Illuminate\Database\Query\Expression  $expression
     * @return string
     */
    public function getValue($expression)
    {
        return $expression->getValue();
    }
}
