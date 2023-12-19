<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Database\Query\Expression;

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

    public function isBind($value, string $type): bool
    {
        if (is_string($value) && preg_match('/^@?[0-9]{4}_' . $value . '_[0-9_]+$/', $value)) {
            return true;
        }

        return false;
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
     * @return string|array
     */
    public function wrap($value, $prefixAlias = false)
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        if (is_array($value)) {
            foreach($value as $key => $subvalue) {
                $value[$key] = $this->wrap($subvalue, $prefixAlias);
            }
            return $value;
        }

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on its
        // own, and then join these both back together using the "as" connector.
        if (is_string($value) && stripos($value, ' as ') !== false) {
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
        if (!$this->isExpression($table)) {
            return $this->wrap($this->tablePrefix . $table, true);
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
        $postfix = '';
        if ($value === 'groupsVariable') {
            $postfix = '[*]';
        }

        if ($value === '*') {
            return $value;
        }

        return '`' . str_replace('`', '``', $value) . '`' . $postfix;
    }

    /**
     * Wrap a subquery single string in braces.
     */
    public function wrapSubquery(string $subquery): string
    {
        return '(' . $subquery . ')';
    }

    public function generateAqlObject(array $data): string
    {
        $data = Arr::undot($data);

        return $this->generateAqlObjectString($data);
    }

    protected function generateAqlObjectString(array $data): string
    {
        foreach($data as $key => $value) {
            $prefix = $key . ': ';
            if (is_numeric($key)) {
                $prefix = '';
            }

            if (is_array($value)) {
                $data[$key] = $prefix . $this->generateAqlObjectString($value);
                continue;
            }

            if ($value instanceof Expression) {
                $data[$key] = $prefix . $value->getValue($this);
                continue;
            }

            $data[$key] = $prefix . $value;
        }

        $returnString = implode(', ', $data);

        return '{' . $returnString . '}';
    }

    /**
     * Substitute the given bindings into the given raw AQL query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return string
     */
    public function substituteBindingsIntoRawSql($sql, $bindings)
    {
        $bindings = array_map(fn ($value) => $this->escape($value), $bindings);

        $bindings = array_reverse($bindings);

        foreach($bindings as $key => $value) {
            $pattern = '/(@'.$key.')(?![^a-zA-Z_ ,\}\]])/';
            $sql = preg_replace(
                $pattern,
                $value,
                $sql
            );
        }

        return $sql;
    }
}
