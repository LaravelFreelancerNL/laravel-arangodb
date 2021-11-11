<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

if (! function_exists('associativeFlatten')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     * List arrays are left untouched
     *
     * @param  iterable  $array
     * @param  string  $prepend
     * @return array
     */
    function associativeFlatten(iterable $array, string $prepend = ''): iterable
    {
        $results = [];

        if (Arr::isAssoc((array) $array)) {
            foreach ($array as $key => $value) {
                if (is_iterable($value) && ! empty($value)) {
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
