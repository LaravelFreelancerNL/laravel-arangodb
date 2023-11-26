<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;

trait CompilesGroups
{
    /**
     * Compile the "group by" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $groups
     * @return string
     */
    protected function compileGroups(IlluminateQueryBuilder $query, $groups)
    {
        $aql = "COLLECT ";

        $aqlGroups = [];
        foreach ($groups as $group) {
            if ($group instanceof Expression) {
                $aqlGroups[] = $group->getValue($this);
                continue;
            }

            $aqlGroups[] = $group . " = " . $this->normalizeColumn($query, $group);
        }

        return $aql . implode(", ", $aqlGroups);
    }
}
