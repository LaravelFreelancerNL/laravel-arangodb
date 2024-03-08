<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Schema\Concerns;

use Illuminate\Support\Fluent;

trait Columns
{
    /**
     * Indicate that the given attributes should be renamed.
     *
     * @param  string  $from
     * @param  string  $to
     * @return Fluent
     */
    public function renameColumn($from, $to)
    {
        $parameters = [];
        $parameters['handler'] = 'aql';
        $parameters['explanation'] = "Rename the column '$from' to '$to'.";
        $parameters['from'] = $from;
        $parameters['to'] = $to;

        return $this->addCommand('renameAttribute', $parameters);
    }

    /**
     * Indicate that the given column(s) should be dropped.
     *
     * @param  array|mixed  $columns
     * @return Fluent
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $parameters = [];
        $parameters['handler'] = 'aql';
        $parameters['attributes'] = $columns;
        $parameters['explanation'] = 'Drop the following column(s): ' . implode(',', $columns) . '.';

        return $this->addCommand('dropColumn', $parameters);
    }
}
