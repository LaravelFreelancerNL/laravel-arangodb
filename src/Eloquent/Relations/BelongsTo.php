<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo as IlluminateBelongsTo;

class BelongsTo extends IlluminateBelongsTo
{
    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->ownerKey, '==', $this->child->{$this->foreignKey});
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     *
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        // We'll grab the primary key name of the related models since it could be set to
        // a non-standard name and not "id". We will then construct the constraint for
        // our eagerly loading query so it returns the proper models from execution.
        $key = $this->ownerKey;

        $whereIn = $this->whereInMethod($this->related, $this->ownerKey);

        $this->query->where($key, 'IN', $this->getEagerModelKeys($models));
    }
}
