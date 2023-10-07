<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar as IlluminateQueryGrammar;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesAggregates;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesColumns;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesGroups;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesJoins;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesWhereClauses;
use LaravelFreelancerNL\Aranguent\Query\Concerns\ConvertsIdToKey;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesAqlGrammar;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HasAliases;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;

class Grammar extends IlluminateQueryGrammar
{
    use CompilesAggregates;
    use CompilesColumns;
    use CompilesJoins;
    use CompilesGroups;
    use CompilesWhereClauses;
    use ConvertsIdToKey;
    use HasAliases;
    use HandlesAqlGrammar;
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
//        'search',
//        'variables',
//        'joins',
        'wheres',
//        'groups',
//        'aggregate',
//        'havings',
//        'orders',
//        'offset',
//        'limit',
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
     * The grammar specific bitwise operators.
     *
     * @var array
     */
    protected $bitwiseOperators = [];

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d\TH:i:s.vp';
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
     * Get the appropriate query parameter place-holder for a value.
     *
     * @param  mixed  $value
     * @return string
     */
    public function parameter($value)
    {

        return $this->isExpression($value) ? $this->getValue($value) : $value;
    }

    /**
     * Compile an insert statement into AQL.
     *
     * @param IlluminateQueryBuilder $builder
     * @param array   $values
     *
     * @throws BindException
     *
     * @return string
     */
    public function compileInsert(Builder|IlluminateQueryBuilder $query, array $values, string $bindVar = null)
    {
        $table = $this->prefixTable($query->from);

        if (empty($values)) {
            $aql = "INSERT {} INTO $table RETURN NEW._key";

            return $aql;
        }

        $aql = "LET values = $bindVar "
                . "FOR value IN values "
                . "INSERT value INTO $table "
                . "RETURN NEW._key";

        return $aql;
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param array<mixed> $values
     */
    public function compileInsertGetId(IlluminateQueryBuilder $builder, $values, $sequence = "_key", string $bindVar = null)
    {
        $table = $this->prefixTable($builder->from);

        if (isset($sequence)) {
            $sequence = $this->convertIdToKey($sequence);
        }

        if (empty($values)) {
            $aql = "INSERT {} INTO $table RETURN NEW.$sequence";

            return $aql;
        }

        $aql = "LET values = $bindVar "
            . "FOR value IN values "
            . "INSERT value INTO $table "
            . "RETURN NEW.$sequence";

        return $aql;
    }

    /**
     * Compile an insert statement into AQL.
     *
     * @param IlluminateQueryBuilder $query
     * @param array<mixed> $values
     * @return string
     */
    public function compileInsertOrIgnore(IlluminateQueryBuilder $query, array $values, string $bindVar = null)
    {
        $table = $this->prefixTable($query->from);

        if (empty($values)) {
            $aql = "INSERT {} INTO $table RETURN NEW._key";

            return $aql;
        }

        $aql = "LET values = $bindVar "
            . "FOR value IN values "
            . "INSERT value INTO $table "
            . "OPTIONS { ignoreErrors: true } "
            . "RETURN NEW._key";

        return $aql;
    }

    /**
     * Compile a select query into SQL.
     *
     * @param IlluminateQueryBuilder $query
     * @return string
     */
    public function compileSelect(IlluminateQueryBuilder $query)
    {
        //        if ($builder->unions && $builder->aggregate) {
        //            return $this->compileUnionAggregate($builder);
        //        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.

        $aql = trim(
            $this->concatenate(
                $this->compileComponents($query)
            )
        );

        //        if ($builder->unions) {
        //            $aql = $this->wrapUnion($aql).' '.$this->compileUnions($builder);
        //        }

        return $aql;
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  IlluminateQueryBuilder  $query
     * @return array
     */
        public function compileTruncate(IlluminateQueryBuilder $query)
        {
            return [$this->compileDelete($query) => []];
        }

    /**
     * Compile the "from" portion of the query -> FOR in AQL.
     *
     * @param IlluminateQueryBuilder $builder
     * @param string  $table
     *
     * @return Builder
     */
    protected function compileFrom(IlluminateQueryBuilder $query, $table)
    {
        // FIXME: wrapping/quoting
        $table = $this->prefixTable($table);

        //FIXME: register given alias (x AS y in SQL)
        $alias = $this->registerTableAlias($table);


        return "FOR $alias IN $table";
    }

    /**
     * @param  IlluminateQueryBuilder  $builder
     * @param  array $variables
     * @return IlluminateQueryBuilder
     */
    protected function compileVariables(IlluminateQueryBuilder $query, array $variables)
    {
        if (! empty($variables)) {
            foreach ($variables as $variable => $data) {
                $query->aqb = $query->aqb->let($variable, $data);
            }
        }

        return $query;
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param IlluminateQueryBuilder $builder
     * @param array   $orders
     *
     * @return IlluminateQueryBuilder
     */
    //    protected function compileOrders(IlluminateQueryBuilder $builder, $orders)
    //    {
    //        if (!empty($orders)) {
    //            $orders = $this->compileOrdersToFlatArray($builder, $orders);
    //            $builder->aqb = $builder->aqb->sort(...$orders);
    //
    //            return $builder;
    //        }
    //
    //        return $builder;
    //    }

    /**
     * Compile the query orders to an array.
     *
     * @param IlluminateQueryBuilder $builder
     * @param array   $orders
     *
     * @return array
     */
    //    protected function compileOrdersToFlatArray(IlluminateQueryBuilder $builder, $orders)
    //    {
    //        $flatOrders = [];
    //
    //        foreach ($orders as $order) {
    //            if (!isset($order['type']) || $order['type'] != 'Raw') {
    //                $order['column'] = $this->normalizeColumn($builder, $order['column']);
    //            }
    //
    //            $flatOrders[] = $order['column'];
    //
    //            if (isset($order['direction'])) {
    //                $flatOrders[] = $order['direction'];
    //            }
    //        }
    //
    //        return $flatOrders;
    //    }

    /**
     * Compile the "offset" portions of the query.
     * We are handling this first by saving the offset which will be used by the FluentAQL's limit function.
     *
     * @param IlluminateQueryBuilder $builder
     * @param int     $offset
     *
     * @return IlluminateQueryBuilder
     */
    //    protected function compileOffset(IlluminateQueryBuilder $builder, $offset)
    //    {
    //        $this->offset = (int) $offset;
    //
    //        return $builder;
    //    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param IlluminateQueryBuilder $builder
     * @param int     $limit
     *
     * @return IlluminateQueryBuilder
     */
    //    protected function compileLimit(IlluminateQueryBuilder $builder, $limit)
    //    {
    //        if ($this->offset !== null) {
    //            $builder->aqb = $builder->aqb->limit((int) $this->offset, (int) $limit);
    //
    //            return $builder;
    //        }
    //        $builder->aqb = $builder->aqb->limit((int) $limit);
    //
    //        return $builder;
    //    }


    /**
     * Compile an update statement into SQL.
     *
     * @param IlluminateQueryBuilder $builder
     * @param array   $values
     *
     * @return IlluminateQueryBuilder
     */
    //    public function compileUpdate(IlluminateQueryBuilder $builder, array $values)
    //    {
    //        $table = $this->prefixTable($builder->from);
    //        $tableAlias = $this->generateTableAlias($table);
    //
    //        $builder->aqb = $builder->aqb->for($tableAlias, $table);
    //
    //        //Fixme: joins?
    //        $builder = $this->compileWheres($builder);
    //
    //        $builder->aqb = $builder->aqb->update($tableAlias, $values, $table);
    //
    //        return $builder;
    //    }

    /**
     * Compile an "upsert" statement into SQL.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param IlluminateQueryBuilder $query
     * @param array $values
     * @param array $uniqueBy
     * @param array $update
     * @return string
     */
    //    public function compileUpsert(IlluminateQueryBuilder $query, array $values, array $uniqueBy, array $update)
    //    {
    //        // Convert id to _key
    //        foreach ($values as $key => $value) {
    //            $values[$key] = $this->convertIdToKey($value);
    //        }
    //
    //        foreach ($uniqueBy as $key => $value) {
    //            $uniqueBy[$key] = $this->convertIdToKey($value);
    //        }
    //
    //        foreach ($update as $key => $value) {
    //            $update[$key] = $this->convertIdToKey($value);
    //        }
    //
    //        /** @phpstan-ignore-next-line */
    //        return DB::aqb()
    //            ->let('docs', $values)
    //            ->for('doc', 'docs')
    //            ->insert('doc', $query->from)
    //            ->options([
    //                "overwriteMode" => "update",
    //                "mergeObjects" => false,
    //            ])->get();
    //    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(IlluminateQueryBuilder $query)
    {
        $table = $query->from;

        $where = $this->compileWheres($query);

        return trim(
            isset($query->joins)
                ? $this->compileDeleteWithJoins($query, $table, $where)
                : $this->compileDeleteWithoutJoins($query, $table, $where)
        );
    }


    /**
     * Compile a delete statement without joins into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  string  $where
     * @return string
     */
    protected function compileDeleteWithoutJoins(IlluminateQueryBuilder $query, $table, $where)
    {

        $alias = $this->normalizeColumn($query, $this->registerTableAlias($table));

        $table = $this->wrapTable($this->prefixTable($table));

        return "FOR {$alias} IN {$table} {$where} REMOVE {$alias} IN {$table}";
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string|int  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        unset($seed);

        return "RAND()";
    }

    /**
     * @param IlluminateQueryBuilder $builder
     * @return IlluminateQueryBuilder
     */
    //    public function compileSearch(IlluminateQueryBuilder $builder): Builder
    //    {
    //        $builder->aqb = $builder->aqb->search($builder->search['predicates']);
    //
    //        if (isset($builder->search['options'])) {
    //            $builder->aqb = $builder->aqb->options($builder->search['options']);
    //        }
    //
    //        return $builder;
    //    }

    /**
     * Get the value of a raw expression.
     *
     * @param  \Illuminate\Database\Query\Expression  $expression
     * @return string
     */
    public function getValue($expression)
    {
        return $expression->getValue($this);
    }

    /**
     * Get the grammar specific bit operators.
     *
     * @return array
     */
    public function getBitwiseOperators()
    {
        return $this->bitwiseOperators;
    }

    /**
     * Prepare the bindings for a delete statement.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindingsForDelete(array $bindings)
    {
        return Arr::collapse(
            Arr::except($bindings, 'select')
        );
    }
}
