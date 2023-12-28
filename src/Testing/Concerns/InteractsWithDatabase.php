<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Testing\Concerns;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Constraints\HasInDatabase;
use Illuminate\Testing\Constraints\NotSoftDeletedInDatabase;
use Illuminate\Testing\Constraints\SoftDeletedInDatabase;
use PHPUnit\Framework\Constraint\LogicalNot as ReverseConstraint;

trait InteractsWithDatabase
{
    /**
     * Assert that a given where condition exists in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array<mixed>  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseHas($table, array $data, $connection = null)
    {
        $this->assertThat(
            $this->getTable($table),
            new HasInDatabase($this->getConnection($connection),associativeFlatten($data))
        );

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array<mixed>  $data
     * @param  string|null  $connection
     * @return $this
     */
    protected function assertDatabaseMissing($table, array $data, $connection = null)
    {
        $constraint = new ReverseConstraint(
            new HasInDatabase($this->getConnection($connection), associativeFlatten($data))
        );

        $this->assertThat($this->getTable($table), $constraint);

        return $this;
    }

    /**
     * Assert the given record has been "soft deleted".
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array<mixed>  $data
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return $this
     */
    protected function assertSoftDeleted(
        $table,
        array $data = [],
        $connection = null,
        $deletedAtColumn = 'deleted_at'
    ) {
        if ($this->isSoftDeletableModel($table) && !is_string($table)) {
            return $this->assertSoftDeleted(
                $table->getTable(),
                [$table->getKeyName() => $table->getKey()],
                $table->getConnectionName(), /** @phpstan-ignore-next-line */
                $table->getDeletedAtColumn()
            );
        }

        $this->assertThat(
            $this->getTable($table),
            new SoftDeletedInDatabase(
                $this->getConnection($connection),
                associativeFlatten($data),
                (string) $deletedAtColumn
            )
        );

        return $this;
    }

    /**
     * Assert the given record has not been "soft deleted".
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array<mixed>  $data
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return $this
     */
    protected function assertNotSoftDeleted(
        $table,
        array $data = [],
        $connection = null,
        $deletedAtColumn = 'deleted_at'
    ) {
        if ($this->isSoftDeletableModel($table) && !is_string($table)) {
            return $this->assertNotSoftDeleted(
                $table->getTable(),
                [$table->getKeyName() => $table->getKey()],
                $table->getConnectionName(), /** @phpstan-ignore-next-line */
                $table->getDeletedAtColumn()
            );
        }

        $this->assertThat(
            $this->getTable($table),
            new NotSoftDeletedInDatabase(
                $this->getConnection($connection),
                associativeFlatten($data),
                (string) $deletedAtColumn
            )
        );

        return $this;
    }

    /**
     * Cast a JSON string to a database compatible type.
     * Supported for backwards compatibility in existing projects.
     * No cast is necessary as json is a first class citizen in ArangoDB.
     *
     * @param  array<mixed>|object|string  $value
     * @return \Illuminate\Contracts\Database\Query\Expression
     */
    public function castAsJson($value)
    {
        return DB::raw($value);
    }

}
