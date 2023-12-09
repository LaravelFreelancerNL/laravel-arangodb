<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesGroups
{
    /**
     * Compile the "group by" portions of the query.
     *
     * @param IlluminateQueryBuilder $query
     * @param array $groups
     * @return string
     * @throws \Exception
     */
    protected function compileGroups(IlluminateQueryBuilder $query, $groups): string
    {
        assert($query instanceof Builder);

        $aql = "COLLECT ";

        $aqlGroups = [];
        foreach ($groups as $group) {
            if ($group instanceof Expression) {
                $groupVariable = $this->extractGroupVariable($group);
                ;
                $query->registerTableAlias($groupVariable, $groupVariable);

                $aqlGroups[] = $group->getValue($this);
                continue;
            }

            $aqlGroups[] = $group . " = " . $this->normalizeColumn($query, $group);

            $query->registerTableAlias($group, $group);
            $query->groupVariables[] = $group;
        }

        $aql .= implode(", ", $aqlGroups);

        $variablesToKeep = $this->keepColumns($query, $groups);

        if (!empty($variablesToKeep)) {
            $query->registerTableAlias('groupsVariable', 'groupsVariable');
            $query->groupVariables[] = 'groupsVariable';

            $aql .= ' INTO groupsVariable = ' . $this->generateAqlObject($variablesToKeep);
        }
        return $aql;
    }

    /**
     * @param IlluminateQueryBuilder $query
     * @param $groups
     * @return array
     * @throws \Exception
     */
    protected function keepColumns(IlluminateQueryBuilder $query, $groups)
    {
        $tempGroups = [];
        foreach($groups as $group) {
            if ($group instanceof Expression) {
                $tempGroups[] = $this->extractGroupVariable($group);
                continue;
            }
            $tempGroups[] = $group;
        }

        $diff = array_diff_assoc($query->columns, $tempGroups);

        $results = [];
        foreach ($diff as $key => $value) {
            if (is_numeric($key)) {
                $results[$value] = $this->normalizeColumn($query, $value);
                continue;
            }
            $results[$key] = $this->normalizeColumn($query, $value);
        }

        return $results;
    }

    protected function extractGroupVariable(Expression $group)
    {
        return explode(' = ', $group->getValue($this))[0];
    }
}
