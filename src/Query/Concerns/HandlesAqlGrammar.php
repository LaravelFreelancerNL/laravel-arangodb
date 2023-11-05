<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

trait HandlesAqlGrammar
{
    /**
     * Available predicate operators.
     *
     * @var array<string, int>
     */
    protected array $comparisonOperators = [
        '=='      => 1,
        '!='      => 1,
        '<'       => 1,
        '>'       => 1,
        '<='      => 1,
        '>='      => 1,
        'IN'      => 1,
        'NOT IN'  => 1,
        'LIKE'    => 1,
        '~'       => 1,
        '!~'      => 1,
        'ALL =='  => 1,
        'ALL !='  => 1,
        'ALL <'   => 1,
        'ALL >'   => 1,
        'ALL <='  => 1,
        'ALL >='  => 1,
        'ALL IN'  => 1,
        'ANY =='  => 1,
        'ANY !='  => 1,
        'ANY <'   => 1,
        'ANY >'   => 1,
        'ANY <='  => 1,
        'ANY >='  => 1,
        'ANY IN'  => 1,
        'NONE ==' => 1,
        'NONE !=' => 1,
        'NONE <'  => 1,
        'NONE >'  => 1,
        'NONE <=' => 1,
        'NONE >=' => 1,
        'NONE IN' => 1,
    ];

    /**
     * @var array|int[]
     */
    protected array $arithmeticOperators = [
        '+' => 1,
        '-' => 1,
        '*' => 1,
        '/' => 1,
        '%' => 1,
    ];

    /**
     * @var array|int[]
     */
    protected array $logicalOperators = [
        'AND' => 1,
        '&&'  => 1,
        'OR'  => 1,
        '||'  => 1,
        'NOT' => 1,
        '!'   => 1,
    ];

    protected string $rangeOperator = '..';

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d\TH:i:s.vp';
    }

    /**
     * Get the appropriate query parameter place-holder for a value.
     *
     * @param  mixed  $value
     */
    public function parameter($value): string
    {
        return $this->isExpression($value) ? $this->getValue($value) : (string) $value;
    }


    /**
     * Quote the given string literal.
     *
     * @param  string|array  $value
     * @return string
     */
    public function quoteString($value)
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, __FUNCTION__], $value));
        }

        return "`$value`";
    }


    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $value
     * @param  bool  $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on its
        // own, and then join these both back together using the "as" connector.
        if (stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value, $prefixAlias);
        }

        return $this->wrapSegments(explode('.', $value));
    }


    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  \Illuminate\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if (! $this->isExpression($table)) {
            //            return $this->tablePrefix . $table;
            return $this->wrap($this->tablePrefix.$table, true);
        }

        return $this->getValue($table);
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '`' . str_replace('`', '``', $value) . '`';
    }
}
