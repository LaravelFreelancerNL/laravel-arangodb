<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query;

use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesAqlGrammar;
use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Database\Query\Grammars\Grammar as IlluminateGrammar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesAggregates;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesColumns;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesGroups;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesJoins;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesWhereClauses;
use LaravelFreelancerNL\Aranguent\Query\Concerns\ConvertsIdToKey;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HasAliases;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;

class Grammar extends IlluminateGrammar
{
    use CompilesAggregates;
    use CompilesColumns;
    use CompilesJoins;
    use CompilesGroups;
    use CompilesWhereClauses;
    use ConvertsIdToKey;
    use HasAliases;
    use Macroable;
    use HandlesAqlGrammar;

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
        'search',
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
        return 'Y-m-d\TH:i:s.v\Z';
    }

    /**
     * Get the grammar specific operators.
     *
     * @return array<mixed>
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
     * @param IlluminateBuilder $query
     * @param array<mixed>   $values
     *
     * @return IlluminateBuilder
     *@throws BindException
     *
     */
    public function compileInsert(IlluminateBuilder $query, array $values)
    {
        if (Arr::isAssoc($values)) {
            $values = [$values];
        }
        $table = $this->prefixTable($query->from);

        if (empty($values)) {
            $query->aqb = $query->aqb->insert('{}', $table);

            return $query;
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $query->aqb = $query->aqb->let('values', $values)
            ->for('value', 'values')
            ->insert('value', $table)
            ->return('NEW._key');

        return $query;
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param array<mixed> $values
     */
    public function compileInsertGetId(IlluminateBuilder $query, $values, $sequence = "_key"): IlluminateBuilder
    {
        if (Arr::isAssoc($values)) {
            $values = [$values];
        }
        $table = $this->prefixTable($query->from);

        if (isset($sequence)) {
            $sequence = $this->convertIdToKey($sequence);
        }

        if (empty($values)) {
            $query->aqb = $query->aqb->insert('{}', $table)
                ->return('NEW.' . $sequence);

            return $query;
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $query->aqb = $query->aqb->let('values', $values)
            ->for('value', 'values')
            ->insert('value', $table)
            ->return('NEW.' . $sequence);

        return $query;
    }

    /**
     * Compile an insert statement into AQL.
     *
     * @param IlluminateBuilder $query
     * @param array<mixed> $values
     * @return IlluminateBuilder
     */
    public function compileInsertOrIgnore(IlluminateBuilder $query, array $values)
    {
        if (Arr::isAssoc($values)) {
            $values = [$values];
        }
        $table = $this->prefixTable($query->from);

        if (empty($values)) {
            $query->aqb = $query->aqb->insert('{}', $table);

            return $query;
        }

        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        $query->aqb = $query->aqb->let('values', $values)
            ->for('value', 'values')
            ->insert('value', $table)
            ->options(["ignoreErrors" => true])
            ->return('NEW._key');

        return $query;
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param IlluminateBuilder $query
     * @return array<mixed>
     */
    public function compileTruncate(IlluminateBuilder $query)
    {
        /** @phpstan-ignore-next-line */
        $aqb = DB::aqb();
        $aqb = $aqb->for('doc', $query->from)->remove('doc', $query->from)->get();
        return [$aqb->query => []];
    }

    /**
     * Compile the "from" portion of the query -> FOR in AQL.
     *
     * @param IlluminateBuilder $query
     * @param string  $table
     *
     * @return IlluminateBuilder
     */
    protected function compileFrom(IlluminateBuilder $query, $table)
    {
        $table = $this->prefixTable($table);
        $alias = $this->registerTableAlias($table);

        return "FOR $alias IN $table";
    }

    /**
     * @param  array<mixed> $variables
     */
    protected function compileVariables(IlluminateBuilder $query, array $variables): string
    {
        if (empty($variables)) {
            return '';
        }

        $lets = [];
        foreach ($variables as $variable => $data) {
            $lets[] = "LET $variable =" . json_encode($data);
        }

        return implode(' ', $lets);
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param IlluminateBuilder $query
     * @param array<mixed>   $orders
     *
     * @return IlluminateBuilder
     */
    protected function compileOrders(IlluminateBuilder $query, $orders)
    {
        if (!empty($orders)) {
            $orders = $this->compileOrdersToFlatArray($query, $orders);
            $query->aqb = $query->aqb->sort(...$orders);

            return $query;
        }

        return $query;
    }

    /**
     * Compile the query orders to an array.
     *
     * @param IlluminateBuilder $query
     * @param array<mixed>   $orders
     *
     * @return array<mixed>
     */
    protected function compileOrdersToFlatArray(IlluminateBuilder $query, $orders)
    {
        $flatOrders = [];

        foreach ($orders as $order) {
            if (!isset($order['type']) || $order['type'] != 'Raw') {
                $order['column'] = $this->normalizeColumn($query, $order['column']);
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
     * @param IlluminateBuilder $query
     * @param int     $offset
     *
     * @return IlluminateBuilder
     */
    protected function compileOffset(IlluminateBuilder $query, $offset)
    {
        $this->offset = (int) $offset;

        return $query;
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param int     $limit
     */
    protected function compileLimit(IlluminateBuilder $query, $limit): string
    {
        if ($this->offset !== null) {
            return 'LIMIT ' . (int) $this->offset . ', ' . (int) $limit;
        }
        return 'LIMIT ' . (int) $limit;
    }


    /**
     * Compile an update statement into SQL.
     *
     * @param IlluminateBuilder $query
     * @param array<mixed>   $values
     *
     * @return IlluminateBuilder
     */
    public function compileUpdate(IlluminateBuilder $query, array $values)
    {

        $table = $this->prefixTable($query->from);
        $tableAlias = $this->generateTableAlias($table);

        $query->aqb = $query->aqb->for($tableAlias, $table);

        //Fixme: joins?
        $query = $this->compileWheres($query);

        $query->aqb = $query->aqb->update($tableAlias, $values, $table);

        return $query;
    }

    /**
     * Compile an "upsert" statement into SQL.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param IlluminateBuilder $query
     * @param array<mixed> $values
     * @param array<mixed> $uniqueBy
     * @param array<mixed> $update
     * @return string
     */
    public function compileUpsert(IlluminateBuilder $query, array $values, array $uniqueBy, array $update)
    {
        // Convert id to _key
        foreach ($values as $key => $value) {
            $values[$key] = $this->convertIdToKey($value);
        }

        foreach ($uniqueBy as $key => $value) {
            $uniqueBy[$key] = $this->convertIdToKey($value);
        }

        foreach ($update as $key => $value) {
            $update[$key] = $this->convertIdToKey($value);
        }

        /** @phpstan-ignore-next-line */
        return DB::aqb()
            ->let('docs', $values)
            ->for('doc', 'docs')
            ->insert('doc', $query->from)
            ->options([
                "overwriteMode" => "update",
                "mergeObjects" => false,
            ])->get();
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @SuppressWarnings(PHPMD.CamelCaseParameterName)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     *
     * @param IlluminateBuilder $query
     * @param null    $id
     *
     * @return IlluminateBuilder
     */
    public function compileDelete(IlluminateBuilder $query, $id = null)
    {
        $table = $this->prefixTable($query->from);
        $tableAlias = $this->generateTableAlias($table);


        if (!is_null($id)) {
            $query->aqb = $query->aqb->remove((string) $id, $table);

            return $query;
        }

        $query->aqb = $query->aqb->for($tableAlias, $table);

        //Fixme: joins?
        $query = $this->compileWheres($query);

        $query->aqb = $query->aqb->remove($tableAlias, $table);

        return $query;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed): string
    {
        return 'RAND()';
    }

    /**
     * @param IlluminateBuilder $query
     * @return IlluminateBuilder
     */
    public function compileSearch(IlluminateBuilder $query): IlluminateBuilder
    {
        $query->aqb = $query->aqb->search($query->search['predicates']);

        if (isset($query->search['options'])) {
            $query->aqb = $query->aqb->options($query->search['options']);
        }

        return $query;
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

    /**
     * Get the grammar specific bit operators.
     *
     * @return array<mixed>
     */
    public function getBitwiseOperators()
    {
        return $this->bitwiseOperators;
    }
}
