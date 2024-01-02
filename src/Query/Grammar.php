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
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesDataManipulations;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesJoins;
use LaravelFreelancerNL\Aranguent\Query\Concerns\CompilesUnions;
use LaravelFreelancerNL\Aranguent\Query\Concerns\ConvertsIdToKey;
use LaravelFreelancerNL\Aranguent\Query\Concerns\HandlesAqlGrammar;

class Grammar extends IlluminateQueryGrammar
{
    use CompilesAggregates;
    use CompilesColumns;
    use CompilesFilters;
    use CompilesDataManipulations;
    use CompilesJoins;
    use CompilesGroups;
    use CompilesUnions;
    use ConvertsIdToKey;
    use HandlesAqlGrammar;
    use Macroable;

    public string $name;

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
     * @var array<string>
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
     * @var array<string>
     */
    protected $selectComponents = [
        'preIterationVariables',
        'from',
        'search',
        'joins',
        'postIterationVariables',
        'wheres',
        'groups',
        'aggregate',
        'havings',
        'orders',
        'offset',
        'limit',
        'columns',
    ];

    /**
     * @var array<string, string>
     */
    protected array $operatorTranslations = [
        '='          => '==',
        '<>'         => '!=',
        '<=>'        => '==',
        'rlike'      => '=~',
        'not rlike'  => '!~',
        'regexp'     => '=~',
        'not regexp' => '!~',
    ];

    /**
     * @var array<string, string>
     */
    protected array $whereTypeOperators = [
        'In'    => 'IN',
        'NotIn' => 'NOT IN',
    ];

    /**
     * The grammar specific bitwise operators.
     *
     * @var array<string>
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
     * @return array<string>
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

    protected function prefixTable(string $table): string
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
     * Compile the components necessary for a select clause.
     *
     * @param  IlluminateQueryBuilder  $query
     * @return array<string, string>
     */
    protected function compileComponents(IlluminateQueryBuilder $query)
    {
        $aql = [];

        foreach ($this->selectComponents as $component) {
            if ($component === 'unions') {
                continue;
            }

            if (isset($query->$component)) {
                $method = 'compile' . ucfirst($component);

                $aql[$component] = $this->$method($query, $query->$component);
            }
        }

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
        assert($query instanceof Builder);

        // If the query does not have any columns set, we'll set the columns to the
        // * character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        $original = $query->columns;

        if (empty($query->columns)) {
            $query->columns = ['*'];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.

        $aql = trim(
            $this->concatenate(
                $this->compileComponents($query)
            )
        );

        //        if ($query->unions && $query->aggregate) {
        //            return $this->compileUnionAggregate($query);
        //        }
        if ($query->unions) {
            return $this->compileUnions($query, $aql);
        }

        $query->columns = $original;

        if ($query->groupVariables !== null) {
            $query->cleanGroupVariables();
        }

        return $aql;
    }

    /**
     * Compile the "from" portion of the query -> FOR in AQL.
     *
     * @param IlluminateQueryBuilder $query
     * @param string  $table
     *
     * @return string
     */
    protected function compileFrom(IlluminateQueryBuilder $query, $table)
    {
        assert($query instanceof Builder);

        // FIXME: wrapping/quoting
        $table = $this->prefixTable($table);

        //FIXME: register given alias (x AS y in SQL)
        $alias = $query->registerTableAlias($table);


        return "FOR $alias IN $table";
    }

    /**
     * @param IlluminateQueryBuilder $query
     * @param array<string, mixed> $variables
     * @return string
     */
    protected function compilePreIterationVariables(IlluminateQueryBuilder $query, array $variables): string
    {
        return $this->compileVariables($query, $variables);
    }

    /**
     * @param IlluminateQueryBuilder $query
     * @param array<string, mixed> $variables
     * @return string
     */
    protected function compilePostIterationVariables(IlluminateQueryBuilder $query, array $variables): string
    {
        return $this->compileVariables($query, $variables);
    }


    /**
     * @param IlluminateQueryBuilder $query
     * @param array<string, mixed> $variables
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function compileVariables(IlluminateQueryBuilder $query, array $variables): string
    {
        $aql = '';

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
     * @param Builder $query
     * @param array<mixed> $orders
     * @param null|string $table
     * @return string
     */
    protected function compileOrders(IlluminateQueryBuilder $query, $orders, $table = null)
    {
        if (!empty($orders)) {
            return 'SORT ' . implode(', ', $this->compileOrdersToArray($query, $orders, $table));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     *
     * @param Builder $query
     * @param array<mixed> $orders
     * @param null|string $table
     * @return array<string>
     * @throws \Exception
     */
    protected function compileOrdersToArray(IlluminateQueryBuilder $query, $orders, $table = null)
    {
        return array_map(function ($order) use ($query, $table) {
            $key = 'column';
            if (array_key_exists('sql', $order)) {
                $key = 'sql';
            }

            if (!$order[$key] instanceof Expression) {
                $order[$key] = $this->normalizeColumn($query, $order[$key], $table);
            }

            if ($order[$key] instanceof Expression) {
                $order[$key] = $order[$key]->getValue($this);
            }

            return array_key_exists('direction', $order) ? $order[$key] . ' ' . $order['direction'] : $order[$key];
        }, $orders);
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function compileLimit(IlluminateQueryBuilder $query, $limit)
    {
        if ($this->offset !== null) {
            return "LIMIT " . (int) $this->offset . ", " . (int) $limit;
        }

        return "LIMIT " . (int) $limit;
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
     * @param array<mixed> $search
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
     * @param bool|float|Expression|int|string|null $expression
     * @return bool|float|int|string|null
     */
    public function getValue($expression)
    {
        if ($expression instanceof Expression) {
            return $expression->getValue($this);
        }

        return $expression;
    }

    /**
     * Get the grammar specific bit operators.
     *
     * @return array<string>
     */
    public function getBitwiseOperators()
    {
        return $this->bitwiseOperators;
    }

    /**
     * Prepare the bindings for a delete statement.
     *
     * @param  array<mixed>  $bindings
     * @return array<mixed>
     */
    public function prepareBindingsForDelete(array $bindings)
    {
        return Arr::collapse(
            Arr::except($bindings, 'select')
        );
    }

    /**
     * Determine if the given value is a raw expression.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }
}
