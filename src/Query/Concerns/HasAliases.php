<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IluminateBuilder;
use Illuminate\Support\Str;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait HasAliases
{

    protected $tableAliases = [];

    protected $columnAliases = [];

    /**
     * @param  string  $table
     * @param  string  $alias
     * @return string
     */
    public function registerTableAlias(string $table, string $alias = null): string
    {
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

    /**
     * @param $table
     * @return mixed|null
     */
    protected function getTableAlias($table)
    {
        if (isset($this->tableAliases[$table])) {
            return $this->tableAliases[$table];
        }

        return null;
    }

    /**
     * Extract table and alias from sql alias notation (entity AS `alias`)
     *
     * @param  string  $entity
     * @return array|false|string[]
     */
    protected function extractAlias(string $entity)
    {
        $results = preg_split("/\sas\s/i", $entity);
        if (isset($results[1])) {
            $results[1] = trim($results[1], '`');
        }
        if (! isset($results[1])) {
            $results[1] = $results[0];
        }

        return $results;
    }

    /**
     * @param $table
     * @param string $postfix
     *
     * @return mixed
     */
    protected function generateTableAlias($table, $postfix = 'Doc')
    {
        return Str::singular($table) . $postfix;
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

    /**
     * @param string  $target
     * @param string  $value
     *
     * @return Builder
     */
    protected function prefixAlias(string $target, string $value): string
    {
        $alias = $this->getTableAlias($target);

        if (Str::startsWith($value, $alias . '.')) {
            return $value;
        }

        return $alias . '.' . $value;
    }

    /**
     * @param  string  $column
     * @param  string|null  $alias
     * @return bool
     */
    protected function registerColumnAlias(string $column, string $alias = null)
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

    /**
     * @param $column
     * @return mixed
     */
    protected function getColumnAlias(string $column)
    {
        if (isset($this->columnAliases[$column])) {
            return $this->columnAliases[$column];
        }

        return null;
    }

    /**
     * @param  IluminateBuilder  $query
     * @param $column
     * @param $table
     * @return string
     */
    protected function normalizeColumn(IluminateBuilder $query, $column, $table = null)
    {
        if ($table == null) {
            $table = $query->from;
        }

        // Replace SQL JSON arrow for AQL dot
        $column = str_replace('->', '.', $column);

        $references = explode('.', $column);

        //We check for an existing alias to determine of the first reference is a table.
        // In which case we replace it with the alias.
        $tableAlias = $this->getTableAlias($references[0]);
        if (isset($tableAlias)) {
            $references[0] = $tableAlias;
        }

        if ($tableAlias === null && ! $this->isTableAlias($references[0])) {
            $tableAlias = $this->generateTableAlias($table);
            array_unshift($references, $tableAlias);
        }

        return implode('.', $references);
    }
}