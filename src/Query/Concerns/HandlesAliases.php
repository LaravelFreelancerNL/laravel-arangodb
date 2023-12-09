<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait HandlesAliases
{
    public array $tableAliases = [];

    public array $columnAliases = [];

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
     * Extract table and alias from sql alias notation (entity AS `alias`)
     *
     * @return array<mixed>
     *
     * @throws Exception
     */
    public function extractAlias(string $entity, int|string $key = null): array
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

    public function generateTableAlias(string|Expression $table, string $postfix = 'Doc'): string
    {
        if ($table instanceof Expression) {
            return 'Expression' . spl_object_id($table);
        }
        return Str::camel(Str::singular($table)) . $postfix;
    }

    public function getTableAlias(string|Expression $table): string|null
    {
        if ($table instanceof Expression) {
            $table = 'Expression' . spl_object_id($table);
        }

        if (isset($this->tableAliases[$table])) {
            return $this->tableAliases[$table];
        }

        return null;
    }

    public function getColumnAlias(string $column): string|null
    {
        if (isset($this->columnAliases[$column])) {
            return $this->columnAliases[$column];
        }

        return null;
    }

    public function getTableAliases(): array
    {
        return $this->tableAliases;
    }

    public function importTableAliases(array|IlluminateQueryBuilder $aliases): void
    {
        if ($aliases instanceof Builder) {
            $aliases = $aliases->getTableAliases();
        }

        $this->tableAliases = array_merge($this->tableAliases, $aliases);
    }

    public function exchangeTableAliases(IlluminateQueryBuilder $query): void
    {
        assert($query instanceof Builder);

        $this->importTableAliases($query);
        $query->importTableAliases($this);
    }

    public function isTableAlias(string $alias)
    {
        return in_array($alias, $this->tableAliases);
    }

    public function prefixAlias(string $target, string $value): string
    {
        $alias = $this->getTableAlias($target);

        if (Str::startsWith($value, $alias . '.')) {
            return $value;
        }

        return $alias . '.' . $value;
    }

    /**
     * @throws Exception
     */
    public function registerColumnAlias(string $column, string $alias = null): bool
    {
        if (preg_match("/\sas\s/i", $column)) {
            [$column, $alias] = $this->extractAlias($column);
        }

        if (isset($alias)) {
            $this->columnAliases[$column] = $alias;

            return true;
        }

        return false;
    }

    public function registerTableAlias(string|Expression $table, string $alias = null): string
    {
        if ($table instanceof Expression  && $alias !== null) {
            $table = 'Expression' . spl_object_id($table);
        }

        if ($table instanceof Expression && $alias === null) {
            $table = 'Expression' . spl_object_id($table);
            $alias = $table;
        }

        if ($alias == null && stripos($table, ' as ') !== false) {
            $tableParts = [];
            preg_match("/(^.*) as (.*?)$/", $table, $tableParts);
            $table = $tableParts[1];
            $alias = $tableParts[2];
        }

        if ($alias == null) {
            $alias = $this->generateTableAlias($table);
        }

        $this->tableAliases[$table] = $alias;

        return $alias;
    }

    public function replaceTableForAlias($reference): string
    {
        $referenceParts = explode('.', $reference);
        $first = array_shift($referenceParts);
        $alias = $this->getTableAlias($first);
        if ($alias == null) {
            $alias = $first;
        }
        array_unshift($referenceParts, $alias);

        return implode('.', $referenceParts);
    }
}
