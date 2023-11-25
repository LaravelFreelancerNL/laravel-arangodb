<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar as IlluminateQueryGrammar;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesAggregates;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesColumns;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesFilters;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesGroups;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesJoins;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesWheres;
use LaravelFreelancerNL\Aranguent\Query\Concerns\ConvertsIdToKey;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesAqlGrammar;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;

class Grammar extends IlluminateQueryGrammar
{
    use CompilesAggregates;
    use CompilesColumns;
    use CompilesFilters;
    use CompilesJoins;
    use CompilesGroups;
    use CompilesWheres;
    use ConvertsIdToKey;
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
     * The grammar specific operators.
     *
     * @var array
     */
    protected $operators = [
        '==', '!=', '<', '>', '<=', '>=',
        'LIKE', '~', '!~',
        'IN', 'NOT IN',
        'ALL ==', 'ALL !=', 'ALL <', 'ALL >', 'ALL <=', 'ALL >=', 'ALL IN',
        'ANY ==', 'ANY !=', 'ANY <', 'ANY >', 'ANY <=', 'ANY >=', 'ANY IN',
        'NONE ==', 'NONE !=', 'NONE <', 'NONE >', 'NONE <=', 'NONE >=', 'NONE IN',
    ];

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'variables',
        'from',
        'search',
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
    public $bitwiseOperators = [
        '&', '|', '^', '<<', '>>', '~',
    ];

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
        return $this->operators;
    }


    public function translateOperator(string $operator): string
    {
        if (array_key_exists($operator, $this->operatorTranslations)) {
            return $this->operatorTranslations[$operator];
        }

        return $operator;
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
     * @param IlluminateQueryBuilder $query
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
            $aql = 'INSERT {} INTO ' . $table . ' RETURN NEW._key';

            return $aql;
        }

        return 'LET values = ' . $bindVar
                . ' FOR value IN values'
                . ' INSERT value INTO ' . $table
                . ' RETURN NEW._key';
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param array<mixed> $values
     */
    public function compileInsertGetId(IlluminateQueryBuilder $query, $values, $sequence = '_key', string $bindVar = null)
    {
        $table = $this->prefixTable($query->from);

        $sequence = $this->convertIdToKey($sequence);

        if (empty($values)) {
            $aql = 'INSERT {} INTO ' . $table . ' RETURN NEW.' . $sequence;

            return $aql;
        }

        $aql = 'LET values = ' . $bindVar
            . ' FOR value IN values'
            . ' INSERT value INTO ' . $table
            . ' RETURN NEW.' . $sequence;

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
     * @param IlluminateQueryBuilder $query
     * @param string  $table
     *
     * @return Builder
     */
    protected function compileFrom(IlluminateQueryBuilder $query, $table)
    {
        // FIXME: wrapping/quoting
        $table = $this->prefixTable($table);

        //FIXME: register given alias (x AS y in SQL)
        $alias = $query->registerTableAlias($table);


        return "FOR $alias IN $table";
    }

    /**
     * @param IlluminateQueryBuilder $query
     * @param array $variables
     * @return string
     */
    protected function compileVariables(IlluminateQueryBuilder $query, array $variables): string
    {
        $aql = "";
        foreach ($variables as $variable => $value) {
            if ($value instanceof Expression) {
                $value = $value->getValue($this);
            }

            $aql .= ' LET ' . $variable . ' = ' . $value;
        }

        return trim($aql);
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(IlluminateQueryBuilder $query, $orders)
    {
        if (!empty($orders)) {
            return 'SORT ' . implode(', ', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $orders
     * @return array
     */
    protected function compileOrdersToArray(IlluminateQueryBuilder $query, $orders)
    {
        return array_map(function ($order) use ($query) {
            $key = 'column';
            if (array_key_exists('sql', $order)) {
                $key = 'sql';
            }

            if ( $order[$key] instanceof Expression) {
                $order[$key] = $order[$key]->getValue($this);
            } else {
                $order[$key] = $this->normalizeColumn($query, $order[$key]);
            }

            return array_key_exists('direction', $order) ? $order[$key].' '.$order['direction'] : $order[$key];
        }, $orders);
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(IlluminateQueryBuilder $query, $offset)
    {
        $this->offset = (int) $offset;

        return "";
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(IlluminateQueryBuilder $query, $limit)
    {
        if ($this->offset !== null) {
            return "LIMIT " . (int) $this->offset . ", " . (int) $limit;
        }

        return "LIMIT " . (int) $limit;
    }

    protected function createUpdateObject($values)
    {
        $valueStrings = [];
        foreach($values as $key => $value) {
            if (is_array($value)) {
                $valueStrings[] = $key . ': ' . $this->createUpdateObject($value);
            } else {
                $valueStrings[] = $key . ': ' . $value;
            }
        }

        return '{ ' . implode(', ', $valueStrings) . ' }';
    }

    /**
     * Compile an update statement into AQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(IlluminateQueryBuilder $query, array|string $values)
    {
        $table = $query->from;
        $alias = $query->getTableAlias($query->from);

        if (!is_array($values)) {
            $values = Arr::wrap($values);
        }

        $updateValues = $this->generateAqlObject($values);

        $aqlElements = [];
        $aqlElements[] = $this->compileFrom($query, $query->from);

        if (isset($query->joins)) {
            $aqlElements[] = $this->compileJoins($query, $query->joins);
        }

        $aqlElements[] = $this->compileWheres($query);

        $aqlElements[] = 'UPDATE ' . $alias . ' WITH ' . $updateValues . ' IN ' . $table;

        return implode(' ', $aqlElements);
    }

    /**
     * Compile an "upsert" statement into AQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @param  array  $uniqueBy
     * @param  array  $update
     * @return string
     */
    public function compileUpsert(IlluminateQueryBuilder $query, array $values, array $uniqueBy, array $update)
    {
        $searchFields = [];
        foreach($uniqueBy as $key => $field) {
            $searchFields[$field] = 'doc.' . $field;
        }
        $searchObject = $this->generateAqlObject($searchFields);

        $updateFields = [];
        foreach($update as $key => $field) {
            $updateFields[$field] = 'doc.' . $field;
        }
        $updateObject = $this->generateAqlObject($updateFields);

        $valueObjects = [];
        foreach($values as $data) {
            $valueObjects[] = $this->generateAqlObject($data);
        }

        return 'LET docs = [' . implode(', ', $valueObjects) . ']'
            . ' FOR doc IN docs'
            . ' UPSERT ' . $searchObject
            . ' INSERT doc'
            . ' UPDATE ' . $updateObject
            . ' IN ' . $query->from;
    }

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

        $alias = $this->normalizeColumn($query, $query->registerTableAlias($table));

        $table = $this->wrapTable($this->prefixTable($table));

        return "FOR {$alias} IN {$table} {$where} REMOVE {$alias} IN {$table}";
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string|int|null  $seed
     * @return string
     */
    public function compileRandom($seed = null)
    {
        unset($seed);

        return "RAND()";
    }

    /**
     * @param IlluminateQueryBuilder $query
     * @return string
     * @throws \Exception
     */
    public function compileSearch(IlluminateQueryBuilder $query, array $search)
    {
        $predicates = [];
        foreach($search['fields'] as $field) {
            $predicates[] = $this->normalizeColumn($query, $field)
                . ' IN TOKENS(' . $search['searchText'] . ', "text_en")';
        }

        return 'SEARCH ANALYZER('
            . implode(' OR ', $predicates)
            . ', "text_en")';
    }

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

    /**
     * Determine if the given string is a JSON selector.
     *
     * @param  string  $value
     * @return bool
     */
    public function isJsonSelector($value)
    {
        if(!is_string($value)) {
            return false;
        }

        return str_contains($value, '->');
    }

    public function convertJsonFields($data): array|string
    {
        if (!is_array($data) && !is_string($data)) {
            return $data;
        }

        if (is_string($data)) {
            return str_replace('->', '.', $data);
        }

        if (array_is_list($data)) {
            return $this->convertJsonValuesToDotNotation($data);
        }

        return $this->convertJsonKeysToDotNotation($data);
    }

    public function convertJsonValuesToDotNotation(array $fields): array
    {
        foreach($fields as $key => $value) {
            if ($this->isJsonSelector($value)) {
                $fields[$key] = str_replace('->', '.', $value);
            }
        }
        return $fields;
    }

    public function convertJsonKeysToDotNotation(array $fields): array
    {
        foreach($fields as $key => $value) {
            if ($this->isJsonSelector($key)) {
                $fields[str_replace('->', '.', $key)] = $value;
                unset($fields[$key]);
            }
        }
        return $fields;
    }
}
