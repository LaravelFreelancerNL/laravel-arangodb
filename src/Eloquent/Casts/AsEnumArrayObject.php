<?php

namespace LaravelFreelancerNL\Aranguent\Eloquent\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject as IlluminateAsEnumArrayObjectAlias;
use Illuminate\Support\Collection;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;

/**
 * @SuppressWarnings("PHPMD.UndefinedVariable")
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
class AsEnumArrayObject extends IlluminateAsEnumArrayObjectAlias
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @template TEnum
     *
     * @param  array{class-string<TEnum>}  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Database\Eloquent\Casts\ArrayObject<array-key, TEnum>, iterable<TEnum>>
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public static function castUsing(array $arguments)
    {
        return new class ($arguments) implements CastsAttributes {
            /**
             * @var array<class-string<TEnum>>
             */
            protected $arguments;

            /**
             * @param array<class-string<TEnum>> $arguments
             *
             * @SuppressWarnings("PHPMD.UndefinedVariable")
             */
            public function __construct(array $arguments)
            {
                $this->arguments = $arguments;
            }

            /**
             * @param $model
             * @param $key
             * @param $value
             * @param $attributes
             * @return ArrayObject|void
             *
             * @SuppressWarnings("PHPMD.UndefinedVariable")
             */
            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return;
                }

                $data = $attributes[$key];
                if (is_object($data)) {
                    $data = (array) $data;
                }

                if (! is_array($data)) {
                    return;
                }

                $enumClass = $this->arguments[0];

                return new ArrayObject((new Collection($data))->map(function ($value) use ($enumClass) {
                    return is_subclass_of($enumClass, BackedEnum::class)
                        ? $enumClass::from($value)
                        : constant($enumClass . '::' . $value);
                })->toArray());
            }

            /**
             * @param Model $model
             * @param string $key
             * @param mixed $value
             * @param mixed[] $attributes
             * @return mixed[]
             *
             * @SuppressWarnings("PHPMD.UnusedFormalParameter")
             * @SuppressWarnings("PHPMD.UndefinedVariable")
             */
            public function set($model, $key, $value, $attributes)
            {
                if ($value === null) {
                    return [$key => null];
                }

                $storable = [];

                foreach ($value as $enum) {
                    $storable[] = $this->getStorableEnumValue($enum);
                }

                return [$key => $storable];
            }

            /**
             * @param Model $model
             * @param string $key
             * @param mixed $value
             * @param mixed[] $attributes
             * @return mixed[]
             */
            public function serialize($model, string $key, $value, array $attributes)
            {
                return (new Collection($value->getArrayCopy()))->map(function ($enum) {
                    return $this->getStorableEnumValue($enum);
                })->toArray();
            }

            /**
             * @param mixed $enum
             * @return int|string
             */
            protected function getStorableEnumValue($enum)
            {
                if (is_string($enum) || is_int($enum)) {
                    return $enum;
                }

                return $enum instanceof BackedEnum ? $enum->value : $enum->name;
            }
        };
    }

    /**
     * Specify the Enum for the cast.
     *
     * @param  class-string  $class
     * @return string
     *
     * @SuppressWarnings("PHPMD.ShortMethodName")
     */
    public static function of($class)
    {
        return static::class . ':' . $class;
    }
}
