<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder as IlluminateEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait HandlesAliases
{
    use GeneratesTableAlias;

    /**
     * @var array<string, Expression|string>
     */
    public array $tableAliases = [];

    /**
     * @var array<string, Expression|string>
     */
    public array $columnAliases = [];

    /**
     * Extract table and alias from sql alias notation (entity AS `alias`)
     *
     * @return array<int|string, Expression|string>
     *
     * @throws Exception
     */
    public function extractAlias(string $entity, int|null|string $key = null): array
    {
        $results = preg_split("/\sas\s/i", $entity);

        if ($results === false) {
            throw new Exception('Column splitting failed');
        }

        if (isset($results[1])) {
            $results[1] = trim($results[1], '`');
        }
        if (!isset($results[1]) && is_string($key)) {
            $results[1] = $key;
        }
        if (!isset($results[1])) {
            $results[1] = $results[0];
        }

        return $results;
    }
    public function getTableAlias(string|Expression $table): string|null
    {
        if ($table instanceof Expression) {
            $table = 'laravel_expression_' . spl_object_id($table);
        }

        if ($this->isTableAlias($table)) {
            return $table;
        }

        $alias = array_search($table, $this->tableAliases, true);

        if (is_string($alias)) {
            return $alias;
        }

        return null;
    }

    public function getColumnAlias(string $column): Expression|null|string
    {
        if (isset($this->columnAliases[$column])) {
            return $this->columnAliases[$column];
        }

        return null;
    }

    /**
     * @return array<string, Expression|string>
     */
    public function getTableAliases(): array
    {
        return $this->tableAliases;
    }

    /**
     * @param array<string, Expression|string>|IlluminateQueryBuilder $aliases
     * @return void
     */
    public function importTableAliases(array|IlluminateQueryBuilder $aliases): void
    {
        if ($aliases instanceof IlluminateQueryBuilder) {
            assert($aliases instanceof Builder);
            $aliases = $aliases->getTableAliases();
        }

        $this->tableAliases = array_merge($this->tableAliases, $aliases);
    }

    /**
     * @param IlluminateEloquentBuilder|IlluminateQueryBuilder|Relation $query
     * @return void
     */
    public function exchangeTableAliases($query): void
    {
        assert($query instanceof Builder);

        $this->importTableAliases($query);
        $query->importTableAliases($this);
    }

    public function isTableAlias(string $value): bool
    {
        return array_key_exists($value, $this->tableAliases);
    }

    public function isTable(string $value): bool
    {
        return !! array_search($value, $this->tableAliases);
    }

    public function prefixAlias(string $target, string $value): string
    {
        /** @phpstan-ignore-next-line */
        $alias = $this->grammar->getValue($this->getTableAlias($target));

        if (Str::startsWith($value, $alias . '.')) {
            return $value;
        }

        return $alias . '.' . $value;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param string|Expression $table
     * @param string|null $alias
     * @return array<string|Expression>
     */
    public function registerTableAlias(string|Expression $table, ?string $alias = null): array
    {
        if ($table instanceof Expression  && $alias !== null) {
            $table = 'laravel_expression_' . spl_object_id($table);
        }

        if ($table instanceof Expression && $alias === null) {
            $table = 'laravel_expression_' . spl_object_id($table);
            $alias = $table;
        }

        /** @phpstan-ignore-next-line  */
        if ($alias == null && is_string($table) && stripos($table, ' as ') !== false) {
            $tableParts = [];

            if (preg_match("/(^.*) as (.*?)$/", $table, $tableParts)) {
                $table = $tableParts[1];
                $alias = $tableParts[2];
            }
        }

        if ($alias == null) {
            $alias = $this->generateTableAlias($table);
        }

        /** @phpstan-ignore-next-line  */
        $this->tableAliases[$alias] = $table;

        return [$table, $alias];
    }

    public function replaceTableForAlias(string $reference): string
    {
        $referenceParts = explode('.', $reference);
        $first = array_shift($referenceParts);
        /** @phpstan-ignore-next-line */
        $alias = $this->grammar->getValue($this->getTableAlias($first));
        if ($alias == null) {
            $alias = $first;
        }
        array_unshift($referenceParts, $alias);

        return implode('.', $referenceParts);
    }

    /**
     * @param  array<mixed>|string  $column
     * @return array<mixed>|string
     */
    public function convertColumnId(array|string|Expression $column): array|string|Expression
    {

        if ($column instanceof Expression) {
            return $column;
        }

        return $this->convertIdToKey($column);
    }

    /**
     * @throws Exception
     */
    public function registerColumnAlias(string $column, ?string $alias = null): bool
    {
        if (preg_match("/\sas\s/i", $column)) {
            [$column, $alias] = $this->extractAlias($column);
        }

        if (isset($alias) && !$column instanceof Expression) {
            $this->columnAliases[$column] = $alias;

            return true;
        }

        return false;
    }
}
