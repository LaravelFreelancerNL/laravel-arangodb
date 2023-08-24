<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns;

trait HasOneOrMany
{
    /**
     * Get the first related record matching the attributes or create it.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes = [], array $values = [])
    {
        /** @phpstan-ignore-next-line */
        $instance = $this->where(associativeFlatten($attributes))->first();
        if (is_null($instance)) {
            $instance = $this->create(array_merge($attributes, $values));
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes = [], array $values = [])
    {
        /** @phpstan-ignore-next-line */
        $instance = $this->where(associativeFlatten($attributes))->first();
        if (is_null($instance)) {
            $instance = $this->related->newInstance(array_merge($attributes, $values));

            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }
}
