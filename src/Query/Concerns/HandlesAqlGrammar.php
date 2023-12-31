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
     * @var array<string, int>
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

    public function isBind(mixed $value): bool
    {
        if (is_string($value) && preg_match('/^@?[0-9]{4}_' . json_encode($value) . '_[0-9_]+$/', $value)) {
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
     * @param  string|array<string>  $value
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
     * @param  Array<mixed>|Expression|string  $value
     * @param  bool  $prefixAlias
     * @return string|array<mixed>
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
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

    /**
     * @param array<mixed> $data
     * @return string
     */
    public function generateAqlObject(array $data): string
    {
        $data = Arr::undot($data);

        return $this->generateAqlObjectString($data);
    }

    /**
     * @param array<mixed> $data
     * @return string
     */
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

        if (array_is_list($data)) {
            return '[' . $returnString . ']';
        }

        return '{' . $returnString . '}';
    }

    /**
     * Substitute the given bindings into the given raw AQL query.
     *
     * @param  string  $sql
     * @param  array<mixed>  $bindings
     * @return string
     */
    public function substituteBindingsIntoRawSql($sql, $bindings)
    {
        $bindings = array_map(fn($value) => $this->escape($value), $bindings);

        $bindings = array_reverse($bindings);

        foreach($bindings as $key => $value) {
            $pattern = '/(@' . $key . ')(?![^a-zA-Z_ ,\}\]])/';
            $sql = preg_replace(
                $pattern,
                $value,
                $sql
            );
        }

        return $sql;
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

    public function convertJsonFields(mixed $data): mixed
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

    /**
     * @param array<string> $fields
     * @return array<string>
     */
    public function convertJsonValuesToDotNotation(array $fields): array
    {
        foreach($fields as $key => $value) {
            if ($this->isJsonSelector($value)) {
                $fields[$key] = str_replace('->', '.', $value);
            }
        }
        return $fields;
    }

    /**
     * @param array<string> $fields
     * @return array<string>
     */
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

    /**
     * Translate sql operators to their AQL equivalent where possible.
     *
     * @param string $operator
     *
     * @return mixed|string
     */
    protected function translateOperator(string $operator)
    {
        if (isset($this->operatorTranslations[strtolower($operator)])) {
            $operator = $this->operatorTranslations[$operator];
        }

        return $operator;
    }

}
