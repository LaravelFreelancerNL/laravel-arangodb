<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Query\Grammar;
use LaravelFreelancerNL\FluentAQL\Exceptions\BindException;

/**
 * @method applyBeforeQueryCallbacks()
 */
trait BuildsUpdates
{
    /**
     * @param array<mixed> $values
     * @return array<mixed>
     */
    protected function prepareValuesForUpdate(array $values)
    {
        foreach($values as $key => $value) {
            if ($value instanceof Expression) {
                $values[$key] = $value->getValue($this->grammar);

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->prepareValuesForUpdate($value);
                continue;
            }

            $values[$key]  = $this->bindValue($value, 'update');
        }

        return $values;
    }

    /**
     * Update records in the database.
     *
     * @param  array<mixed>  $values
     * @return int
     */
    public function update(array $values)
    {
        assert($this->grammar instanceof Grammar);

        $this->applyBeforeQueryCallbacks();

        $values = Arr::undot($this->grammar->convertJsonFields($values));

        $values = $this->prepareValuesForUpdate($values);

        $aql = $this->grammar->compileUpdate($this, $values);

        return $this->connection->update($aql, $this->getBindings());
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param array<mixed> $attributes
     * @param array<mixed> $values
     * @return bool
     * @throws BindException
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        if (!$this->where($attributes)->exists()) {
            $this->bindings['where'] = [];
            return $this->insert(array_merge($attributes, $values));
        }

        if (empty($values)) {
            return true;
        }

        return (bool) $this->limit(1)->update($values);
    }

    /**
     * Increment the given column's values by the given amounts.
     *
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public function incrementEach(array $columns, array $extra = [])
    {
        foreach ($columns as $column => $amount) {
            if (!is_numeric($amount)) {
                throw new InvalidArgumentException("Non-numeric value passed as increment amount for column: '$column'.");
            } elseif (!is_string($column)) {
                throw new InvalidArgumentException('Non-associative array passed to incrementEach method.');
            }

            $columns[$column] = new Expression($this->getTableAlias($this->from) . '.' . $column . ' + ' . $amount);
        }

        return $this->update(array_merge($columns, $extra));
    }

    /**
     * Decrement the given column's values by the given amounts.
     *
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public function decrementEach(array $columns, array $extra = [])
    {
        foreach ($columns as $column => $amount) {
            if (!is_numeric($amount)) {
                throw new InvalidArgumentException("Non-numeric value passed as decrement amount for column: '$column'.");
            } elseif (!is_string($column)) {
                throw new InvalidArgumentException('Non-associative array passed to decrementEach method.');
            }

            $columns[$column] = new Expression($this->getTableAlias($this->from) . '.' . $column . ' - ' . $amount);
        }

        return $this->update(array_merge($columns, $extra));
    }

    /**
     * Insert new records or update the existing ones.
     *
     * @param array<mixed> $values
     * @param array<mixed>|string $uniqueBy
     * @param array<mixed>|null $update
     * @return int
     * @throws BindException
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        assert($this->grammar instanceof Grammar);

        if (empty($values)) {
            return 0;
        } elseif ($update === []) {
            return (int) $this->insert($values);
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        foreach($values as $key => $value) {
            $values[$key] = $this->grammar->convertJsonFields($value);
            $values[$key] = $this->convertIdToKey($values[$key]);
            $values[$key] = Arr::undot($values[$key]);
        }

        foreach($values as $key => $value) {
            foreach ($value as $dataKey => $data) {
                $values[$key][$dataKey] = $this->bindValue($data, 'upsert');
            }
        }

        $uniqueBy = $this->grammar->convertJsonFields($uniqueBy);

        if (is_null($update)) {
            $update = array_keys(reset($values));
        }

        foreach ($update as $key => $value) {
            $update[$key] = $this->convertIdToKey($value);
        }

        $update = $this->grammar->convertJsonFields($update);

        $this->applyBeforeQueryCallbacks();

        $bindings = $this->bindings['upsert'];

        $aql = $this->grammar->compileUpsert($this, $values, (array) $uniqueBy, $update);

        return $this->connection->affectingStatement(
            $aql,
            $bindings
        );
    }
}
