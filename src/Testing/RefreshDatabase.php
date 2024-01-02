<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Testing;

use Illuminate\Foundation\Testing\RefreshDatabase as IlluminateRefreshDatabase;

trait RefreshDatabase
{
    use HandlesTestingTransactions;
    use IlluminateRefreshDatabase;

    /**
     * Begin a database transaction on the testing database.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $this->initializeTestDatabaseTransactions();
    }
}
