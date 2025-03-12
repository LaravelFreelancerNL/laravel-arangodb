<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject as IlluminateAsArrayObject;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;

class AsArrayObject extends IlluminateAsArrayObject
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array<array-key, mixed>  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Database\Eloquent\Casts\ArrayObject<array-key, mixed>, iterable<array-key, mixed>>
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes {
            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                $data = $attributes[$key];

                if (is_object($data)) {
                    $data = mapObjectToArray($data);
                }

                return is_array($data) ? new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS) : null;
            }

            /**
             * @param Model $model
             * @param string $key
             * @param mixed $value
             * @param mixed[] $attributes
             * @return mixed[]
             *
             * @SuppressWarnings("PHPMD.UnusedFormalParameter")
             */
            public function set($model, $key, $value, $attributes)
            {
                return [$key => $value];
            }

            /**
             * @param Model $model
             * @param string $key
             * @param mixed $value
             * @param mixed[] $attributes
             * @return mixed
             */
            public function serialize($model, string $key, $value, array $attributes)
            {
                return $value->getArrayCopy();
            }
        };
    }
}
