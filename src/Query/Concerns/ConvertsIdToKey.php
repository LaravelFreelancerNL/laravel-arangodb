<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

trait ConvertsIdToKey
{
    public function convertIdToKey($data)
    {
        if (is_array($data) && array_is_list($data)) {
            foreach($data as $key => $value) {
                $data[$key] = $this->convertIdInString($value);
            }
            return $data;
        }

        if (!is_array($data) && !is_string($data)) {
            return $data;
        }

        if (is_string($data)) {
            return $this->convertIdInString($data);
        }

        return $this->convertIdInArrayKeys($data);
    }

    protected function convertIdInString(string $data): string
    {
        $replace = [
            "/^id$/" => '_key',
            "/\.id$/" => '._key'
        ];
        //TODO: we probably only want to replace .id if the prefix is a table or table alias.

        return preg_replace(
            array_keys($replace),
            $replace,
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
            if (!is_string($key)) {
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
