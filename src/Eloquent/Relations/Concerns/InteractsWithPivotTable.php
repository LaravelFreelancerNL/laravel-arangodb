<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Relations\Concerns;

trait InteractsWithPivotTable
{
    /**
     * Create a full attachment record payload.
     *
     * @param  int  $key
     * @param  mixed  $value
     * @param  array<mixed>  $attributes
     * @param  bool  $hasTimestamps
     * @return array<mixed>
     */
    protected function formatAttachRecord($key, $value, $attributes, $hasTimestamps)
    {
        [$id, $attributes] = $this->extractAttachIdAndAttributes($key, $value, $attributes);

        /**
         * When attaching multiple models with pivot data PHP will cast a numeric-string array key to an integer
         * By setting the type here we recast it to the keyType set on the model.
         */
        settype($id, $this->related->getKeyType());

        return array_merge(
            $this->baseAttachRecord($id, $hasTimestamps),
            $this->castAttributes($attributes)
        );
    }
}
