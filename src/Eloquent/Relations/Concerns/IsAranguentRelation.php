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
     * @param  Builder  $query
     * @param  Builder  $parentQuery
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
