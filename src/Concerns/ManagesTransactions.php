<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Concerns;

use Closure;
use Throwable;

trait ManagesTransactions
{
    /**
     * Execute a Closure within a transaction.
     *
     * @param  Closure  $callback
     * @param  int  $attempts
     * @param  array  $collections
     * @return mixed
     *
     * @throws Throwable
     */
    public function transaction(Closure $callback, $attempts = 1, $collections = [])
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction($collections);

            // We'll simply execute the given callback within a try / catch block and if we
            // catch any exception we can rollback this transaction so that none of this
            // gets actually persisted to a database or stored in a permanent fashion.
            try {
                $callbackResult = $callback($this);
            } catch (Throwable $e) {
                // If we catch an exception we'll rollback this transaction and try again if we
                // are not out of attempts. If we are out of attempts we will just throw the
                // exception back out and let the developer handle an uncaught exceptions.

                $this->handleTransactionException(
                    $e,
                    $currentAttempt,
                    $attempts
                );

                continue;
            }

            try {
                if ($this->transactions == 1) {
                    $this->commit();
                }

                $this->transactions = max(0, $this->transactions - 1);
            } catch (Throwable $e) {
                $this->handleCommitTransactionException(
                    $e,
                    $currentAttempt,
                    $attempts
                );

                continue;
            }

            $this->fireConnectionEvent('committed');

            return $callbackResult;
        }
    }

    /**
     * Start a new database transaction.
     *
     * @param array<string, array<string>> $collections
     * @throws Throwable
     */
    public function beginTransaction(array $collections = []): void
    {
        $this->createTransaction($collections);

        $this->transactions++;

        $this->fireConnectionEvent('beganTransaction');
    }

    /**
     * Create a transaction within the database.
     *
     * @param array<string, array<string>> $collections
     * @throws Throwable
     */
    protected function createTransaction(array $collections = []): void
    {
        if ($this->transactions == 0) {
            $this->reconnectIfMissingConnection();

            try {
                $this->arangoClient->begin($collections);
            } catch (Throwable $e) {
                $this->handleBeginTransactionException($e);
            }
        }
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     *
     * @throws Throwable
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->arangoClient->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed');
    }

    /**
     * Rollback the active database transaction.
     *
     * @param  int|null  $toLevel
     * @return void
     *
     * @throws \Throwable
     */
    public function rollBack($toLevel = null)
    {
        // We allow developers to rollback to a certain transaction level. We will verify
        // that this given transaction level is valid before attempting to rollback to
        // that level. If it's not we will just return out and not attempt anything.
        $toLevel = is_null($toLevel)
            ? $this->transactions - 1
            : $toLevel;

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        // Next, we will actually perform this rollback within this database and fire the
        // rollback event. We will also set the current transaction level to the given
        // level that was passed into this method so it will be right from here out.
        try {
            $this->performRollBack($toLevel);
        } catch (Throwable $e) {
            $this->handleRollBackException($e);
        }

        $this->transactions = $toLevel;

        $this->fireConnectionEvent('rollingBack');
    }

    /**
     * Perform a rollback within the database.
     *
     * @param  int  $toLevel
     * @return void
     *
     * @throws Throwable
     */
    protected function performRollBack($toLevel)
    {
        if ($toLevel == 0) {
            $this->arangoClient->abort();
        }
    }
}
