<?php

namespace LaravelFreelancerNL\Aranguent\Concerns;

use ArangoDBClient\StreamingTransaction;
use ArangoDBClient\TransactionBase;
use Closure;
use Throwable;

trait ManagesTransactions
{
    protected $transactions = 0;

    protected $arangoTransaction;

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
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
                    $this->commitArangoTransaction();
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
     * @param  array  $collections
     * @return void
     *
     * @throws Throwable
     */
    public function beginTransaction($collections = [])
    {
        $this->createTransaction($collections);

        $this->transactions++;

        $this->fireConnectionEvent('beganTransaction');
    }

    /**
     * Create a transaction within the database.
     *
     * @param  array  $collections
     * @return void
     *
     */
    protected function createTransaction($collections = [])
    {
        if ($this->transactions == 0) {
            $this->reconnectIfMissingConnection();

            try {
                $this->beginArangoTransaction($collections);
            } catch (Throwable $e) {
                $this->handleBeginTransactionException($e);
            }
        }
    }

    protected function beginArangoTransaction($collections = [])
    {
        $transactionHandler = $this->getTransactionHandler();

        $this->arangoTransaction = new StreamingTransaction($this->getArangoConnection(), [
            TransactionBase::ENTRY_COLLECTIONS => $collections
        ]);
        $this->arangoTransaction = $transactionHandler->create($this->arangoTransaction);
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
            $this->commitArangoTransaction();
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed');
    }

    /**
     *  Commit the transaction through the ArangoDB driver.
     */
    protected function commitArangoTransaction()
    {
        $transactionHandler = $this->getTransactionHandler();

        $transactionHandler->commit($this->arangoTransaction);

        $this->arangoTransaction = null;
    }

    /**
     * Perform a rollback within the database.
     *
     * @param  int  $toLevel
     * @return void
     *
     * @throws \Throwable
     */
    protected function performRollBack($toLevel)
    {
        if ($toLevel == 0) {
            $transactionHandler = $this->getTransactionHandler();

            $transactionHandler->abort($this->arangoTransaction);
            $this->arangoTransaction = null;
        }
    }

    public function getArangoTransaction()
    {
        return $this->arangoTransaction;
    }
}
