<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;

trait CompilesGroups
{
    /**
     * Compile the "group by" portions of the query.
     *
     * @param IlluminateQueryBuilder $query
     * @param  array  $groups
     * @return string
     */
    //    protected function compileGroups(IlluminateQueryBuilder $query, $groups)
    //    {
    //        $aqlGroups = [];
    //        foreach ($groups as $key => $group) {
    //            $aqlGroups[$key][0] = $group;
    //
    //            $aqlGroups[$key][1] = $this->normalizeColumn($builder, $group);
    //        }
    //
    //        $builder->aqb = $builder->aqb->collect($aqlGroups);
    //
    //        return $builder;
    //    }

    /**
     * Compile the "having" portions of the query.
     *
     * @param IlluminateQueryBuilder $query
     * @return string
     */
    //    protected function compileHavings(IlluminateQueryBuilder $query)
    //    {
    //        return $this->compileWheres($query);
    //    }
}
