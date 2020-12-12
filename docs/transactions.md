# Transactions
Transactions work quite differently on ArangoDB compared to SQL databases, so be sure to check out the documentation.
Transactions are send to ArangoDB on commit. If they fail they are rolled back by the DB.
You can start a transaction as normal with DB::transaction($callback) 
or through the DB::beginTransaction() & db::commit() combo.

All of the connection's CRUD and statement methods are transaction aware and picked up if you started a transaction. 

Alternatively you can use the following commands to add a query or command to the transaction respectively.
`addQueryToTransaction($query, $bindings = [], $collections = null)`

`addTransactionCommand(IlluminateFluent $command)`

## Preventing read write deadlocks
Transaction queries that involve a write action must be provided with a list of involved connections.
This is also preferred for read connections but not required. If no collection list is provided with a query 
the driver will try to extract them from the query. If that fails the transaction will fail and rollback. 
