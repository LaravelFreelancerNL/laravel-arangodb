<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;

trait CompilesInserts
{
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
     * @param array<mixed> $values
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
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
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


        //        if ($insertDoc !== '') {
        //            $insertValues;
        //        }

        $aql = /** @lang AQL */ 'LET docs = ' . $sql
            . ' FOR docDoc IN docs'
            . ' INSERT ' . $insertDoc . ' INTO ' . $table
            . ' RETURN NEW._key';


        return $aql;
    }


}
