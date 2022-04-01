<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesGroups
{
    /**
     * Compile the "group by" portions of the query.
     *
     * @param Builder $builder
     * @param array<string> $groups
     * @return Builder
     * @throws \Exception
     */
    protected function compileGroups(Builder $builder, array $groups = []): Builder
    {
        $aqlGroups = [];
        foreach ($groups as $key => $group) {
            $aqlGroups[$key][0] = $group;

            $aqlGroups[$key][1] = $this->normalizeColumn($builder, $group);
        }

        $builder->aqb = $builder->aqb->collect($aqlGroups);

        return $builder;
    }
    /**
     * Compile the "group by" portions of the query.
     *
     * @param Builder $builder
     * @param string[]  $havings
     * @return Builder
     */
    protected function compileHavings(Builder $builder, array $havings = [])
    {
        return $this->compileWheres($builder, $havings, 'havings');
    }
}
