<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

trait IsAranguentRelation
{
    /**
     * Add the constraints for a relationship count query.
     *
     * @return Builder
     */
    public function getRelationExistenceCountQuery(Builder $query, Builder $parentQuery)
    {
        return $this->getRelationExistenceQuery(
            $query,
            $parentQuery,
            new Expression('*')
        );
    }
}
