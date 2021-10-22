<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Testing;

use Illuminate\Foundation\Testing\DatabaseTransactions as IlluminateDatabaseTransactions;

trait DatabaseTransactions
{
    use HandlesTestingTransactions;
    use IlluminateDatabaseTransactions;

    /**
     * Handle database transactions on the specified connections.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $this->initializeTestDatabaseTransactions();
    }
}
