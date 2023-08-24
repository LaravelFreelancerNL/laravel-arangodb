<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as IlluminateBelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as IlluminateBelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as IlluminateHasMany;
use Illuminate\Database\Eloquent\Relations\HasOne as IlluminateHasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough as IlluminateHasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany as IlluminateMorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne as IlluminateMorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo as IlluminateMorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany as IlluminateMorphToMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\BelongsTo;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\BelongsToMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\HasMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\HasOne;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\HasOneThrough;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\MorphMany;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\MorphOne;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\MorphTo;
use LaravelFreelancerNL\Aranguent\Eloquent\Relations\MorphToMany;

trait HasAranguentRelationships
{
    /**
     * Instantiate a new BelongsTo relationship.
     *
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return IlluminateBelongsTo
     */
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Instantiate a new BelongsToMany relationship.
     *
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @return IlluminateBelongsToMany
     */
    protected function newBelongsToMany(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null
    ) {
        return new BelongsToMany(
            $query,
            $parent,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relationName
        );
    }

    /**
     * Instantiate a new HasMany relationship.
     *
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return IlluminateHasMany
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Instantiate a new HasOne relationship.
     *
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return IlluminateHasOne
     */
    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasOne($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Instantiate a new HasOneThrough relationship.
     *
     * @param  string  $firstKey
     * @param  string  $secondKey
     * @param  string  $localKey
     * @param  string  $secondLocalKey
     * @return IlluminateHasOneThrough
     */
    protected function newHasOneThrough(
        Builder $query,
        Model $farParent,
        Model $throughParent,
        $firstKey,
        $secondKey,
        $localKey,
        $secondLocalKey
    ) {
        return new HasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    /**
     * Instantiate a new MorphMany relationship.
     *
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return IlluminateMorphMany
     */
    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new MorphMany($query, $parent, $type, $id, $localKey);
    }

    /**
     * Instantiate a new MorphOne relationship.
     *
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return IlluminateMorphOne
     */
    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new MorphOne($query, $parent, $type, $id, $localKey);
    }

    /**
     * Instantiate a new MorphTo relationship.
     *
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $type
     * @param  string  $relation
     * @return IlluminateMorphTo
     */
    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        return new MorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }

    /**
     * Instantiate a new MorphToMany relationship.
     *
     *  Laravel API PHPMD exclusions
     *
     *  @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *  @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @param  bool  $inverse
     * @return IlluminateMorphToMany
     */
    protected function newMorphToMany(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
        $inverse = false
    ) {
        return new MorphToMany(
            $query,
            $parent,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relationName,
            $inverse
        );
    }
}
