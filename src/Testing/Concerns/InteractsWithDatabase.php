<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Testing\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Constraints\HasInDatabase;
use Illuminate\Testing\Constraints\NotSoftDeletedInDatabase;
use Illuminate\Testing\Constraints\SoftDeletedInDatabase;
use LaravelFreelancerNL\Aranguent\Testing\TestCase;
use PHPUnit\Framework\Constraint\LogicalNot as ReverseConstraint;

trait InteractsWithDatabase
{
    /**
     * Assert that a given where condition exists in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return self|TestCase
     */
    protected function assertDatabaseHas($table, array $data, $connection = null)
    {
        $this->assertThat(
            $this->getTable($table),
            new HasInDatabase($this->getConnection($connection), Arr::dot($data))
        );

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @return self|TestCase
     */
    protected function assertDatabaseMissing($table, array $data, $connection = null)
    {
        $constraint = new ReverseConstraint(
            new HasInDatabase($this->getConnection($connection), Arr::dot($data))
        );

        $this->assertThat($this->getTable($table), $constraint);

        return $this;
    }

    /**
     * Assert the given record has been "soft deleted".
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return \Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase
     */
    protected function assertSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
    {
        if ($this->isSoftDeletableModel($table)) {
            return $this->assertSoftDeleted(
                $table->getTable(),
                [$table->getKeyName() => $table->getKey()],
                $table->getConnectionName(),
                $table->getDeletedAtColumn()
            );
        }

        $this->assertThat(
            $this->getTable($table),
            new SoftDeletedInDatabase(
                $this->getConnection($connection),
                Arr::dot($data),
                $deletedAtColumn
            )
        );

        return $this;
    }

    /**
     * Assert the given record has not been "soft deleted".
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $table
     * @param  array  $data
     * @param  string|null  $connection
     * @param  string|null  $deletedAtColumn
     * @return \Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase
     */
    protected function assertNotSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
    {
        if ($this->isSoftDeletableModel($table)) {
            return $this->assertNotSoftDeleted(
                $table->getTable(),
                [$table->getKeyName() => $table->getKey()],
                $table->getConnectionName(),
                $table->getDeletedAtColumn()
            );
        }

        $this->assertThat(
            $this->getTable($table),
            new NotSoftDeletedInDatabase(
                $this->getConnection($connection),
                Arr::dot($data),
                $deletedAtColumn
            )
        );

        return $this;
    }
}
