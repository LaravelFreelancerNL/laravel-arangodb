<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Support\Arr;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesDataManipulations
{
    /**
     * Compile an insert statement into AQL.
     *
     * @param Builder|IlluminateQueryBuilder $query
     * @param array<mixed> $values
     * @param string|null $bindVar
     * @return string
     */
    public function compileInsert(Builder|IlluminateQueryBuilder $query, array $values, string $bindVar = null)
    {
        $table = $this->prefixTable($query->from);

        if (empty($values)) {
            $aql = /** @lang AQL */ 'INSERT {} INTO ' . $table . ' RETURN NEW._key';

            return $aql;
        }

        return /** @lang AQL */ 'LET values = ' . $bindVar
            . ' FOR value IN values'
            . ' INSERT value INTO ' . $table
            . ' RETURN NEW._key';
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param IlluminateQueryBuilder $query
     * @param array<mixed> $values
     * @param null|string $sequence
     * @param string|null $bindVar
     * @return string
     */
    public function compileInsertGetId(IlluminateQueryBuilder $query, $values, $sequence = '_key', string $bindVar = null)
    {
        $table = $this->prefixTable($query->from);

        $sequence = $this->convertIdToKey($sequence);

        if (empty($values)) {
            $aql = /** @lang AQL */ 'INSERT {} INTO ' . $table . ' RETURN NEW.' . $sequence;

            return $aql;
        }

        $aql = /** @lang AQL */ 'LET values = ' . $bindVar
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
            $aql = /** @lang AQL */ "INSERT {} INTO $table RETURN NEW._key";

            return $aql;
        }

        $aql = /** @lang AQL */ "LET values = $bindVar "
            . "FOR value IN values "
            . "INSERT value INTO $table "
            . "OPTIONS { ignoreErrors: true } "
            . "RETURN NEW._key";

        return $aql;
    }

    /**
     * Compile an insert statement using a subquery into SQL.
     *
     * @param  IlluminateQueryBuilder  $query
     * @param  array<mixed>  $columns
     * @param  string  $sql
     * @return string
     */
    public function compileInsertUsing(IlluminateQueryBuilder $query, array $columns, string $sql)
    {
        $table = $this->wrapTable($query->from);

        $insertDoc = '';
        if (empty($columns) || $columns === ['*']) {
            $insertDoc = 'docDoc';
        }


        if ($insertDoc === '') {
            $insertValues = [];
            foreach($columns as $column) {
                $insertValues[$column] = $this->normalizeColumnReferences($query, $column, 'docs');
            }
            $insertDoc = $this->generateAqlObject($insertValues);
        }

        $aql = /** @lang AQL */ 'LET docs = ' . $sql
            . ' FOR docDoc IN docs'
            . ' INSERT ' . $insertDoc . ' INTO ' . $table
            . ' RETURN NEW._key';


        return $aql;
    }

    /**
     * @param array<mixed> $values
     * @return string
     */
    protected function createUpdateObject($values)
    {
        $valueStrings = [];
        foreach($values as $key => $value) {
            if (is_array($value)) {
                $valueStrings[] = $key . ': ' . $this->createUpdateObject($value);

                continue;
            }

            $valueStrings[] = $key . ': ' . $value;
        }

        return '{ ' . implode(', ', $valueStrings) . ' }';
    }

    /**
     * Compile an update statement into AQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<mixed>  $values
     * @return string
     */
    public function compileUpdate(IlluminateQueryBuilder $query, array|string $values)
    {
        assert($query instanceof Builder);

        $table = $query->from;
        $alias = $query->getTableAlias($query->from);

        if (!is_array($values)) {
            $values = Arr::wrap($values);
        }

        $updateValues = $this->generateAqlObject($values);

        $aqlElements = [];
        $aqlElements[] = $this->compileFrom($query, $query->from);

        if (!empty($query->joins)) {
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
     * @param  array<mixed>  $values
     * @param  array<mixed>  $uniqueBy
     * @param  array<mixed>  $update
     * @return string
     */
    public function compileUpsert(IlluminateQueryBuilder $query, array $values, array $uniqueBy, array $update)
    {
        $searchFields = [];
        foreach($uniqueBy as $field) {
            $searchFields[$field] = 'doc.' . $field;
        }
        $searchObject = $this->generateAqlObject($searchFields);

        $updateFields = [];
        foreach($update as $field) {
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
            !empty($query->joins)
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
        assert($query instanceof Builder);

        $alias = $this->normalizeColumn($query, $query->registerTableAlias($table));

        $table = $this->wrapTable($this->prefixTable($table));

        return "FOR {$alias} IN {$table} {$where} REMOVE {$alias} IN {$table}";
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  IlluminateQueryBuilder  $query
     * @return array<mixed>
     */
    public function compileTruncate(IlluminateQueryBuilder $query)
    {
        return [$this->compileDelete($query) => []];
    }

}
