<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor as IlluminateProcessor;

class Processor extends IlluminateProcessor
{
    /**
     * Process the results of a "select" query.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param  array<mixed>|null  $results
     * @return array<mixed>
     */
    public function processSelect(Builder $query, $results)
    {
        if ($results === null) {
            return [];
        }

        foreach ($results as &$val) {
            if (is_object($val) && isset($val->_key)) {
                $val = (array) $val;
                renameArrayKey($val, '_key', 'id');
                $val = (object) $val;
            }
        }

        return $results;
    }
}
