<?php

namespace LaravelFreelancerNL\Aranguent\Concerns;

use Closure;
use Exception;
use Illuminate\Support\Fluent as IlluminateFluent;
use ArangoDBClient\Transaction as ArangoTransaction;

trait ManagesTransactions
{
    protected $transactions = 0;

    protected $transactionCommands = [];

    protected $arangoTransaction;

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  array  $options
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback, $options = [], $attempts = 1)
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
     * @param \Illuminate\Support\Fluent $command
     */
    public function addTransactionCommand(IlluminateFluent $command)
    {
        $this->transactionCommands[$this->transactions][] = $command;
    }

    /**
     * Add a query command to the transaction.
     *
     * @param $query
     * @param $bindings
     * @param array|null $collections
     * @return IlluminateFluent
     */
    public function addQueryToTransaction($query, $bindings = [], $collections = null)
    {
        //If transaction collections aren't provided we will try to extract them from the query.
        if (empty($collections)) {
            $collections = $this->extractTransactionCollections($query, $bindings, $collections);
        }

//        $query = addslashes($query);
        $jsCommand  = "db._query('".$query."'";
        if (! empty($bindings)) {
            $bindings = json_encode($bindings);
            $jsCommand .= ", ".$bindings;
        }
        $jsCommand .= ");";
        $command = new IlluminateFluent([
            'name' => 'aqlQuery',
            'command' => $jsCommand,
            'collections' => $collections,
        ]);

        $this->addTransactionCommand($command);

        return $command;
    }

    /**
     * Transaction like a list of read collections to prevent possible read deadlocks.
     * Transactions require a list of write collections to prepare write locks.
     *
     * @param $query
     * @param $bindings
     * @param $collections
     * @return mixed
     */
    public function extractTransactionCollections($query, $bindings, $collections)
    {
        //Extract write collections
        $collections = $this->extractReadCollections($query, $bindings, $collections);
        $collections = $this->extractWriteCollections($query, $bindings, $collections);

        return $collections;
    }

    /**
     * Extract collections that are read from in a query. Not required but can prevent deadlocks
     *
     * @param $query
     * @param $bindings
     * @param $collections
     * @return mixed
     */
    public function extractReadCollections($query, $bindings, $collections)
    {
        $extractedCollections = [];
        //WITH statement at the start of the query
        preg_match_all('/^WITH([\S\s]*?)FOR/im', $query, $rawWithCollections);
        foreach ($rawWithCollections[1] as $key => $value) {
            $splits = preg_split("/\s*,\s*/", $value);
            $extractedCollections = array_merge($extractedCollections, $splits);
        }

        //FOR statements
        preg_match_all('/FOR (?:\w+) (?:IN|INTO) (?!OUTBOUND|INBOUND|ANY)(@?@?\w+(?!\.))/im', $query, $rawForCollections);
        $extractedCollections = array_merge($extractedCollections, $rawForCollections[1]);

        //Document functions which require a document as their first argument
        preg_match_all('/(?:DOCUMENT\(|ATTRIBUTES\(|HAS\(|KEEP\(|LENGTH\(|MATCHES\(|PARSE_IDENTIFIER\(|UNSET\(|UNSET_RECURSIVE\(|VALUES\(|OUTBOUND|INBOUND|ANY)\s?(?!\{)(?:\"|\'|\`)(@?@?\w+)\/(?:\w+)(?:\"|\'|\`)/im', $query, $rawDocCollections);
        $extractedCollections = array_merge($extractedCollections, $rawDocCollections[1]);

        $extractedCollections = array_map('trim',$extractedCollections);

        $extractedCollections = $this->getCollectionByBinding($extractedCollections, $bindings);

        if (isset($collections['read'])) {
            $collections['read'] = array_merge($collections['read'], $extractedCollections);
        } else {
            $collections['read'] = $extractedCollections;
        }

        $collections['read'] = array_unique($collections['read']);

        return $collections;
    }

    /**
     * Extract collections that are written to in a query
     *
     * @param $query
     * @param $bindings
     * @param $collections
     * @return mixed
     */
    public function extractWriteCollections($query, $bindings, $collections)
    {
        preg_match_all('/(?:INSERT|UPSERT|UPDATE|REPLACE|REMOVE)(?:[\S\s]{.*}?)(?:IN|INTO) (@?@?\w+)/im', $query, $extractedCollections);
        $extractedCollections = array_map('trim',$extractedCollections[1]);

        $extractedCollections = $this->getCollectionByBinding($extractedCollections, $bindings);

        if (isset($collections['write'])) {
            $collections['write'] = array_merge($collections['write'], $extractedCollections);
        } else {
            $collections['write'] = $extractedCollections;
        }

        $collections['read'] = array_unique($collections['read']);

        return $collections;
    }

    /**
     * Get the collection names that are bound in a query.
     *
     * @param $collections
     * @param $bindings
     * @return mixed
     */
    public function getCollectionByBinding($collections, $bindings)
    {
        foreach ($collections as $key => $collection) {
            if (strpos($collection, '@@') === 0 && isset($bindings[$collection])) {
                $collections[$key] = $bindings[$collection];
            }
        }

        return $collections;
    }

    /**
     * Commit the current transaction.
     *
     * @param array $options
     * @param integer $attempts
     * @return mixed
     * @throws Exception
     */
    public function commit($options = [], $attempts = 1)
    {
        if (! $this->transactions > 0) {
            throw new \Exception('Transaction committed before starting one.');
        }
        if (! isset($this->transactionCommands[$this->transactions]) || empty($this->transactionCommands[$this->transactions])) {
            throw new \Exception('Cannot commit an empty transaction.');
        }

        $options['collections'] = $this->compileTransactionCollections();

        $options['action'] = $this->compileTransactionAction();

        $results = $this->executeTransaction($options, $attempts);

        $this->fireConnectionEvent('committed');

        return $results;
    }

    public function executeTransaction($options, $attempts = 1)
    {
        $results = null;

        $this->arangoTransaction = new ArangoTransaction($this->arangoConnection, $options);

        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            try {
                $results = $this->arangoTransaction->execute();

                $this->transactions--;
            } catch (Exception $e) {
                $this->fireConnectionEvent('rollingBack');

                $results = $this->handleTransactionException($e, $currentAttempt, $attempts);
            }
        }

        return $results;
    }

    /**
     * Handle an exception encountered when running a transacted statement.
     *
     * @param $e
     * @param $currentAttempt
     * @param $attempts
     * @return mixed
     */
    protected function handleTransactionException($e, $currentAttempt, $attempts)
    {
        $retry = false;
        // If the failure was due to a lost connection we can just try again.
        if ($this->causedByLostConnection($e)) {
            $this->reconnect();
            $retry = true;
        }

        // Retry if the failure was caused by a deadlock or ArangoDB suggests we try so.
        // We can check if we have exceeded the maximum attempt count for this and if
        // we haven't we will return and try this transaction again.
        if ($this->causedByDeadlock($e) &&
            $currentAttempt < $attempts) {
            $retry = true;
        }

        if ($retry) {
            return $this->arangoTransaction->execute();
        }

        throw $e;
    }

    /**
     * compile an array of unique collections that are used to read from and/or write to.
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

        $result['read'] = array_merge($result['read'], $result['write']);

        $result['write'] = array_filter(array_unique($result['write']));
        if (empty($result['write'] )) {
            unset($result['write']);
        }

        $result['read'] = array_filter(array_unique($result['read']));
        if (empty($result['read'] )) {
            unset($result['read']);
        }

        $result = array_filter($result);

        return $result;
    }

    public function compileTransactionAction()
    {
        $commands = collect($this->transactionCommands[$this->transactions]);

        $action = "function () { var db = require('@arangodb').db; ";
        $action .= $commands->implode('command', ' ');
        $action .= ' }';

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
     * Dummy.
     *
     * @param $e
     */
    public function handleBeginTransactionException($e)
    {
        //
    }

    /**
     * Dummy override: Rollback the active database transaction.
     *
     * @param  int|null  $toLevel
     * @return void
     *
     * @throws \Exception
     */
    public function rollBack($toLevel = null)
    {
        //
    }

    /**
     * Dummy override: ArangoDB rolls back the entire transaction on a failure.
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
     * Not supported by ArangoDB(?).
     *
     * @return void
     */
    protected function createSavepoint()
    {
        //
    }
}
