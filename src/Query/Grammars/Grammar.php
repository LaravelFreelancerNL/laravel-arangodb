<?php

namespace LaravelFreelancerNL\Aranguent\Query\Grammars;

use Illuminate\Database\Grammar as IlluminateBaseGrammar;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException as BindException;
use LaravelFreelancerNL\FluentAQL\Facades\AQB;
use LaravelFreelancerNL\FluentAQL\Grammar as FluentAqlGrammar;
use LaravelFreelancerNL\FluentAQL\QueryBuilder as FluentAQL;

/*
 * Provides AQL syntax functions
 */
class Grammar extends FluentAqlGrammar
{

    use Macroable;

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
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'lock',
        'aggregate',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'offset',
        'limit',
        'columns',
    ];

    protected function generateVariable($table, $postfix = 'Doc')
    {
        return Str::singular($table).$postfix;
    }

    protected function prefixTable($table)
    {
        return $this->tablePrefix.$table;
    }

    /**
     * Compile an insert statement into AQL.
     *
     * @param Builder $query
     * @param array $values
     * @return string
     * @throws BindException
     */
    public function compileInsert(Builder $query, array $values)
    {
        $table = $this->prefixTable($query->from);

        if (empty($values)) {
            return AQB::insert('{}', $table);
        }

        $qb = new FluentAQL();
        return $qb->insert($qb->bind($values), $table)
            ->return('NEW._key')
            ->get();
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param Builder $query
     * @param array $values
     * @return string
     * @throws BindException
     */
    public function compileInsertGetId(Builder $query, $values)
    {
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile a select query into AQL.
     *
     * @param  Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
//        if ($query->unions && $query->aggregate) {
//            return $this->compileUnionAggregate($query);
//        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.

        $aqb = new FluentAQL();
        $aqb = $this->compileComponents($query, $aqb);


//        if ($query->unions) {
//            $sql = $this->wrapUnion($sql).' '.$this->compileUnions($query);
//        }


        return $aqb->get();
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param Builder $query
     * @param FluentAQL $aqb
     * @return array
     */
    protected function compileComponents(Builder $query, FluentAQL $aqb)
    {
        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.

            if (isset($query->$component) && ! is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $aqb = $this->$method($query, $aqb, $query->$component);
            }
        }

        return $aqb;
    }


    /**
     * Compile the "from" portion of the query -> FOR in AQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param FluentAQL $aqb
     * @param string $table
     * @return FluentAQL
     */
    protected function compileFrom(\Illuminate\Database\Query\Builder $query, FluentAQL $aqb, $table)
    {
         return $aqb->for($this->generateVariable($table), $this->prefixTable($table));
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param Builder $query
     * @param FluentAQL $aqb
     * @param $table
     * @return string
     */
    protected function compileWheres(Builder $query, FluentAQL $aqb)
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        if (is_null($query->wheres)) {
            return $aqb;
        }

        if (count($predicates = $this->compileWheresToArray($query)) > 0) {
            return $aqb->filter($predicates);
        }

        return $aqb;
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        $result = collect($query->wheres)->map(function ($where) use ($query) {
            // ArangoDB uses a double '=' for comparison
            if ($where['operator'] == '=') {
                $where['operator'] = '==';
            }
            return [
                $where['column'],
                $where['operator'],
                $where['value'],
                $where['boolean']
            ];
        })->all();
        return $result;
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param Builder $query
     * @param FluentAQL $aqb
     * @param array $orders
     * @return string
     */
    protected function compileOrders(Builder $query, FluentAQL $aqb, $orders)
    {
        if (! empty($orders)) {
            return $aqb->sort($this->compileOrdersToArray($query, $orders));
        }

        return $aqb;
    }

    /**
     * Compile the query orders to an array.
     *
     * @param  Builder  $query
     * @param  array  $orders
     * @return FluentAQL
     */
    protected function compileOrdersToArray(Builder $query, $orders)
    {
        return array_map(function ($order) {
            return $order['sql'] ?? $this->prefixTable($order['column']).' '.$order['direction'];
        }, $orders);
    }

    /**
     * Compile the "offset" portions of the query.
     * We are handling this first by saving the offset which will be used by the FluentAQL's limit function
     *
     * @param Builder $query
     * @param FluentAQL $aqb
     * @param int $offset
     * @return string
     */
    protected function compileOffset(Builder $query, FluentAQL $aqb, $offset)
    {
        $this->offset = (int) $offset;

        return $aqb;
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param Builder $query
     * @param FluentAQL $aqb
     * @param int $limit
     * @return string
     */
    protected function compileLimit(Builder $query, FluentAQL $aqb, $limit)
    {
        if ($this->offset !== null) {
            return $aqb->limit((int)$this->offset, (int)$limit);
        }
        return $aqb->limit((int) $limit);
    }


    /**
     * Compile the "select *" portion of the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param FluentAQL $aqb
     * @param array $columns
     * @return string|null
     */
    protected function compileColumns(\Illuminate\Database\Query\Builder $query, FluentAQL $aqb, array $columns)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
//        if (! is_null($query->aggregate)) {
//            return;
//        }

        $values = [];
        $doc = $this->generateVariable($query->from);
        foreach ($columns as $column) {
            if ($column != null && $column != '*') {
                $values[$column] = $doc.'.'.$column;
            }
        }
        if (empty($values)) {
            $values = $doc;
        }
        return $aqb->return($values, (boolean) $query->distinct);
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param Builder $query
     * @param FluentAQL $aqb
     * @param array $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values)
    {
        $aqb =  $query->aqb;
        $table = $this->prefixTable($query->from);
        $tableVariable = $this->generateVariable($table);
        $aqb = $aqb->for($tableVariable, $table);

        //Fixme: joins?
        $aqb = $this->compileWheres($query, $aqb);

        $aqb = $aqb->update($tableVariable, $values, $table)->get();
        return $aqb;
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param Builder $query
     * @param null $_key
     * @return string
     */
    public function compileDelete(Builder $query, $_key = null)
    {
        $aqb =  $query->aqb;
        $table = $this->prefixTable($query->from);
        if (! is_null($_key)) {
            return $aqb->remove((string) $_key, $table)->get();
        }

        $tableVariable = $this->generateVariable($table);
        $aqb = $aqb->for($tableVariable, $table);

        //Fixme: joins?
        $aqb = $this->compileWheres($query, $aqb);
        $aqb = $aqb->remove($tableVariable, $table)->get();
        return $aqb;
    }

}
