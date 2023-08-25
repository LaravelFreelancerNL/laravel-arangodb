<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Exception;
use Illuminate\Support\Str;

trait HasAliases
{
    protected $tableAliases = [];

    protected $columnAliases = [];

    public function registerTableAlias(string $table, string $alias = null): string
    {
        if ($alias == null && stripos($table, ' as ') !== false) {
            [$table, $alias] = explode(' as ', $table);
        }
        if ($alias == null) {
            $alias = $this->generateTableAlias($table);
        }
        $this->tableAliases[$table] = $alias;

        return $alias;
    }

    protected function isTableAlias(string $alias)
    {
        return in_array($alias, $this->tableAliases);
    }

    protected function getTableAlias(string $table): string|null
    {
        if (isset($this->tableAliases[$table])) {
            return $this->tableAliases[$table];
        }

        return null;
    }

    /**
     * Extract table and alias from sql alias notation (entity AS `alias`)
     *
     * @return array<mixed>
     *
     * @throws Exception
     */
    protected function extractAlias(string $entity, int|string $key = null): array
    {
        $results = preg_split("/\sas\s/i", $entity);

        if ($results === false) {
            throw new Exception('Column splitting failed');
        }

        if (isset($results[1])) {
            $results[1] = trim($results[1], '`');
        }
        if (! isset($results[1]) && is_string($key)) {
            $results[1] = $key;
        }
        if (! isset($results[1])) {
            $results[1] = $results[0];
        }

        return $results;
    }

    protected function generateTableAlias(string $table, string $postfix = 'Doc'): string
    {
        return Str::camel(Str::singular($table)).$postfix;
    }

    protected function replaceTableForAlias($reference): string
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

    protected function prefixAlias(string $target, string $value): string
    {
        $alias = $this->getTableAlias($target);

        if (Str::startsWith($value, $alias.'.')) {
            return $value;
        }

        return $alias.'.'.$value;
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

    protected function getColumnAlias(string $column): string|null
    {
        if (isset($this->columnAliases[$column])) {
            return $this->columnAliases[$column];
        }

        return null;
    }

    /**
     * @param  array<mixed>|string  $column
     * @return array<mixed>|string
     */
    protected function convertColumnId(array|string $column): array|string
    {
        return $this->convertIdToKey($column);
    }
}
