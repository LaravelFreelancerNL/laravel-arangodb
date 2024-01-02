<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Testing;

trait WithArangoDb
{
    use Concerns\InteractsWithDatabase;

    /**
     * @var array<string, array<string>>
     */
    protected array $transactionCollections = [];

    /**
     * @param  array<string, array<string>>  $transactionCollections
     */
    public function setTransactionCollections(array $transactionCollections): void
    {
        $this->transactionCollections = $transactionCollections;
    }
}
