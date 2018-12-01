Aranguent
---------
<p align="center">
<a href="https://travis-ci.org/LaravelFreelancerNL/aranguent"><img src="https://travis-ci.org/LaravelFreelancerNL/aranguent.svg?branch=master" alt="Build Status"></a>
<a href="https://github.styleci.io/repos/127011133"><img src="https://github.styleci.io/repos/127011133/shield?branch=master" alt="Code style"></a>
<a href="https://packagist.org/packages/laravel-freelancer-nl/aranguent"><img src="https://poser.pugx.org/laravel-freelancer-nl/aranguent/v/stable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel-freelancer-nl/aranguent"><img src="https://poser.pugx.org/laravel-freelancer-nl/aranguent/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel-freelancer-nl/aranguent"><img src="https://poser.pugx.org/laravel-freelancer-nl/aranguent/license" alt="License"></a>

[ArangoDB](https://www.arangodb.com) driver for [Laravel](https://laravel.com)  
<sub>The unguent between the ArangoDB and Laravel</sub>
</p>

## About Aranguent
The goal is to create a drop-in ArangoDB replacement for Laravel's database, migrations and model handling.

## This package is in development; DO NOT use in production.

## Table Of Contents
**[Installation](#installation)**<br>
**[Connection](#connection)**<br>
**[Migrations](#migrations)**<br>
**[Converting Existing Laravel Projects](#converting-existing-laravel-projects)**<br>

Installation
------------
You may use composer to install Aranguent:

``` composer require laravel-freelancer-nl/aranguent ```

### Version compatibility
| Laravel  | ArangoDB            | Aranguent Package |
| :------- | :------------------ | :---------------- |
| 5.7.x    | 3.3.x or 3.4.0-rc.x | 0.x.x             |


Connection
----------
1) Add a new database connection to `config/database.php`

```
        'arangodb' => [
            'name'       => 'arangodb',
            'driver'     => 'arangodb',
            'endpoint'   => env('DB_ENDPOINT', 'tcp://localhost:8529'),
            'database'   => env('DB_DATABASE'),
            'AuthUser'   => env('DB_USERNAME'),
            'AuthPasswd' => env('DB_PASSWORD'),
        ],
```
Provide an array of endpoints to handle failover if you have a replication set up. 

The endpoint for the default ArangoDB docker image is 'tcp://arangodb:8529'.

2) Set your default connection `DB_CONNECTION=arangodb` in your .env file
or in `config/database.php` with `'default' => env('DB_CONNECTION', 'arangodb'),`

Migrations
----------
You can use the regular migration Artisan commands.

## Blueprint execution
Blueprint commands are executed sequentially and non-transactional(!)

## Collections
You can add ArangoDB's collection config options to the create method.

``` 
Schema::create('posts', function (Blueprint $collection) {
    //
}, [
        'type' => 3,            // 2 -> normal collection, 3 -> edge-collection</li>
        'waitForSync' => true,  // if set to true, then all removal operations will instantly be synchronised to disk / If this is not specified, then the collection's default sync behavior will be applied.</li>
]);
 ```
See the [ArangoDB Documentation for all options](https://docs.arangodb.com/3.3/HTTP/Collection/Creating.html)

## Indexes
Within the collection blueprint you can create indexes.
ArangoDB knows the following types:

Type       | Purpose                 | Blueprint Method 
---------- | ----------------------- | ----------------
Hash       | Exact matching          | `$collection->hashIndex($attributes, $indexOptions = [])`
Skip List  | Ranged matching         | `$collection->skiplistIndex($attributes, $indexOptions = [])`
Persistent | Ranged matching         | `$collection->persistentIndex($attributes, $indexOptions = [])`
Geo        | Location matching       | `$collection->geoIndex($attributes, $indexOptions = [])`
Fulltext   | (Partial) text matching | `$collection->fulltextIndex($attribute, $indexOptions = [])`

See the [ArangoDB Documentation for more information](https://docs.arangodb.com/3.3/HTTP/Indexes/)

### Dropping Indexes
Use `$collection->dropIndex($attributes, $type)` to drop an index from within a Blueprint.

## Attributes
ArangoDB is schemaless so you can't create attributes. However you can perform some operations on 
attributes in existing documents.

`dropAttribute($attributes)` deletes the attribute(s) in all documents within the collection.

`hasAttribute($attribute)` Checks for the usage (! null) of the the attribute(s) in any document within the collection.
 
`renameAttribute($from, $to)` renames the attribute in all documents within the collection.

Transactions
------------
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

Converting Existing Laravel Projects
------------------------------------
An existing Laravel project can't be fully converted now as the new AQL query builder isn't implemented yet. 
Migrations can be converted as well as raw queries against the database. 

### Migrations
Existing Illuminate based migration files can be quickly converted to use the Aranguent Schema Builder with the following 
Artisan command: 

``` php artisan aranguent:convert-migrations  ```

As ArangoDB is schemaless, column creation methods are ignored. However, index creation on a column is caught and handled: 

`$table->string('email')->unique()` creates a unique skiplist index for the 'email' attribute.

The `->autoIncrement()` column modifier and `$table->increments('id');` column definition will set autoincrement on the 
collections _key if used during collection creation. This is solely for developer expectations management.  

#### Indexes
Index creation defaults to skiplist indexes. If you solely need exact matching you may want to change your index 
to a hash index as these provide better performance.

Note that each document in ArangoDB has two primary keys with hash indexes bij default: _key and _id ({collection}/{_key})

`renameIndex()` is ignored as indexes are unnamed in ArangoDB.

`dropIndex` needs to provide the type and attribute(s) of the index in order to work.
`dropPrimary` is ignored, you can't drop the primary indexes in ArangoDB.
`dropUnique` and `dropSpatialIndex` need to be rewritten to dropIndex.

### Raw SQL queries
You will need to convert any raw SQL queries to AQL. 
The ArangoDB documentation provides examples for common equivalents between the two languages.

### Transactions
Transactions work differently now so check out the documentation to see how you can use them.

### Connection CRUD & statement methods
 These methods now accept an additional argument 'transactionCollections' to support transactions.
 
### Query builder
Use of the query builder will throw. 