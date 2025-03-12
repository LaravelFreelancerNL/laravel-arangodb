<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Concerns;

trait HasAttributes
{
    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        return $this->hasCast($key, ['encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object']);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string  $value
     * @param  bool  $asObject
     * @return mixed
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function fromJson($value, $asObject = false)
    {
        // As data is stored as json in ArangoDB we don't have to decode it here.
        if ($asObject) {
            return (object) $value;
        }

        // Recursively cast objects within the value to arrays
        return mapObjectToArray($value);
    }
}
