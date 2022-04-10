<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesGroups
{
    /**
     * Compile the "group by" portions of the query.
     *
     * @param array<mixed> $groups
     * @throws \Exception
     */
    protected function compileGroups(IlluminateBuilder $query, $groups): string
    {
        $aqlGroups = [];
        foreach ($groups as $key => $group) {
            $aqlGroups[$key][0] = $group;

            $aqlGroups[$key][1] = $this->normalizeColumn($query, $group);
        }

        return 'COLLECT ' . json_encode($aqlGroups);
    }

    /**
     * Compile the "having" portions of the query.
     *
     * @param IlluminateBuilder $query
     * @return string
     */
    protected function compileHavings(IlluminateBuilder $query)
    {
        return $this->compileWheres($query, $query->havings, 'havings');
    }
}
