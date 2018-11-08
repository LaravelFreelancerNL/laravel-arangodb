<?php

namespace LaravelFreelancerNL\Aranguent\Concerns;

use ArangoDBClient\Transaction;
use Closure;
use Exception;
use Illuminate\Support\Fluent;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use Throwable;

trait ManagesTransactions
{
    protected $transactions = 0;

    protected $transactionCommands = [];

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback, $options = [],  $attempts = 1)
    {
        $this->beginTransaction();

        return tap($callback($this), function () use ($options, $attempts) {
            $this->commit($options, $attempts);
        });
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function beginTransaction()
    {
        $this->transactions++;

        $this->transactionCommands[$this->transactions] = [];

        $this->fireConnectionEvent('beganTransaction');
    }

    /**
     * Add a command to the transaction. Parameters must include:
     * collections['write'][]: collections that are written to
     * collections['read'][]: collections that are read from
     * command: the db command to execute.
     *
     * @param $name
     * @param array $parameters
     */
    public function addCommandToTransaction($name, array $parameters = [])
    {
        $illegalCommands = [
            'createDatabase',
            'dropDatabase',
            'createCollection',
            'renameCollection',
            'dropCollection',
            'createIndex',
            'dropIndex'
        ];

        if (in_array($name, $illegalCommands)) {
            throw new ExceptionMessage("$name ({$parameters['command']}) cannot be used in an ArangoDB transaction.");
        }

        $this->transactionCommands[$this->transactions][] = new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Commit the current transaction.
     *
     * @param array $options
     * @return Transaction
     * @throws Exception
     */
    public function commit($options = [], $attempts = 1)
    {
        if (! $this->transactions > 0) {
            throw new Exception("Transaction committed before starting one.");
        }
        if (! isset($this->transactionCommands[$this->transactions]) || empty($this->transactionCommands[$this->transactions])) {
            throw new Exception("Cannot commit an empty transaction.");
        }

        $options['collections'] = $this->compileTransactionCollections();

        $options['action'] = $this->compileTransactionAction();

        $results = $this->executeTransaction($options, $attempts);

        $this->fireConnectionEvent('committed');

        return $results;
    }

    public function executeTransaction($options, $attempts = 1)
    {
        $transaction = new Transaction($this->arangoConnection, $options);

        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            try {
                $results = $transaction->execute();

                $this->transactions--;

            } catch (Exception $e) {
                $results = $this->handleTransactionException($e, $currentAttempt, $attempts, $transaction);
            }
        }

        return $results;
    }

    /**
     * Handle an exception encountered when running a transacted statement.
     *
     * @param $e
     * @param $transaction
     * @param $currentAttempt
     * @param $attempts
     * @return mixed
     */
    protected function handleTransactionException($e, $currentAttempt, $attempts, $transaction = null)
    {
        $retry = false;
        // If the failure was due to a lost connection we can just try again.
        if ($this->causedByLostConnection($e)) {
            $this->reconnect();

            $retry = true;
        }

        // If the failure was caused by a deadlock or ArangoDB suggests we try again we do so.. We can
        // check if we have exceeded the maximum attempt count for this and
        // if we haven't we will return and try this transaction again.
        if ($this->causedByDeadlock($e) &&
            $currentAttempt < $attempts) {
            $retry = true;
        }

        if ($retry) {
            return $transaction->execute();
        }

        throw $e;
    }

    /**
     * compile an array of unique collections that are used to read from and/or write to
     *
     * @return array
     */
    public function compileTransactionCollections()
    {
        $result['write'] = [];
        $result['read'] = [];

        $commands = $this->transactionCommands[$this->transactions];

        foreach ($commands as $command) {
            if (isset($command->collections['write'])) {
                $write = $command->collections['write'];
                if (is_string($write)) {
                    $write = (array) $write;
                }
                $result['write'] = array_merge($result['write'], $write);
            }
            if (isset($command->collections['read'])) {
                $read = $command->collections['read'];
                if (is_string($read)) {
                    $read = (array) $read;
                }
                $result['read'] = array_merge($result['write'], $read);
            }
        }
        $result['write'] = array_filter(array_unique($result['write']));
        $result['read'] = array_filter(array_unique($result['read']));
        $result = array_filter($result);

        return $result;
    }

    public function compileTransactionAction()
    {
        $commands = collect($this->transactionCommands[$this->transactions]);

        $action = "function () { var db = require('@arangodb').db; ";
        $action .= $commands->implode('command'," ");
        $action .= " }";
        return $action;
    }



    /**
     * Handle an exception from a rollback.
     *
     * @param \Exception  $e
     *
     * @throws \Exception
     */
    protected function handleRollBackException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->transactions = 0;
        }

        throw $e;
    }

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }

    public function getTransactionCommands()
    {
        return $this->transactionCommands;
    }

    //Override unused trait transaction functions with dummy methods

    /**
     * Dummy
     *
     * @param $e
     */
    public function handleBeginTransactionException($e)
    {
        //
    }


    /**
     * Rollback the active database transaction.
     * FIXME: should the 'rollingBack' event be fired or not?
     *
     * @param  int|null  $toLevel
     * @return void
     *
     * @throws \Exception
     */
    public function rollBack($toLevel = null)
    {
        //
        // $this->fireConnectionEvent('rollingBack');
    }

    /**
     * Deprecated: ArangoDB rolls back the entire transaction on a failure.
     *
     * @param  int  $toLevel
     * @return void
     */
    protected function performRollBack($toLevel)
    {
        //
    }

    /**
     * Create a save point within the database.
     * Not supported by ArangoDB
     *
     * @return void
     */
    protected function createSavepoint()
    {
        //
    }
}
