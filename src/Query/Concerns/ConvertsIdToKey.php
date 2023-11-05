<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Support\Arr;

trait ConvertsIdToKey
{
    protected function convertIdToKey(array|string $data): array|string
    {
        if (is_string($data)) {
            return $this->convertIdInString($data);
        }

        if (! Arr::isAssoc($data)) {
            return $data;
        }

        return $this->convertIdInArrayKeys($data);
    }

    protected function convertIdInString(string $data): string
    {
        return preg_replace(
            "/^id$/",
            '_key',
            $data,
            1
        );
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    protected function convertIdInArrayKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $newKey = $this->convertIdInString($key);
            $data[$newKey] = $value;
            if ($key !== $newKey) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
