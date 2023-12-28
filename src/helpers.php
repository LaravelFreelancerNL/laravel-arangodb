<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

if (!function_exists('associativeFlatten')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     * List arrays are left untouched.
     *
     * @param array<mixed> $array
     * @param string $prepend
     * @return array<mixed>
     */
    function associativeFlatten(array $array, string $prepend = ''): array
    {
        $results = [];

        if (Arr::isAssoc((array) $array)) {
            foreach ($array as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $dot = '';
                    if (Arr::isAssoc($value)) {
                        $dot = '.';
                    }
                    $results = array_merge($results, associativeFlatten($value, $prepend . $key . $dot));

                    continue;
                }
                $results[$prepend . $key] = $value;
            }

            return $results;
        }
        $results[$prepend] = $array;

        return $results;
    }
}

if (!function_exists('isDotString')) {
    function isDotString(string $string): bool
    {
        return (bool) strpos($string, '.');
    }
}

if (!function_exists('renameArrayKey')) {
    /**
     * @param  array<mixed>  $array
     * @return array<mixed>
     */
    function renameArrayKey(array &$array, string|int $oldKey, string|int $newKey): array
    {
        if (array_key_exists($oldKey, $array)) {
            $keys = array_keys($array);
            $keys[array_search($oldKey, $keys)] = $newKey;
            $array = array_combine($keys, $array);
        }

        return $array;
    }
}
