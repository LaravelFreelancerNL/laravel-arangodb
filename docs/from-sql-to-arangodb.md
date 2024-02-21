# Converting Existing Laravel Projects
An existing Laravel project can't be fully converted now as the new AQL query builder isn't implemented yet. 
Migrations can be converted as well as raw queries against the database. 

## id vs _id
ArangoDB has a two default identifiers '_key' and _'id'.
'_key' is a similar identifier as commonly used in SQL databases by default it is a numeric string.
'_id' is a combination of the collection and the key. For example: users/12345

The query builder automatically translates _key to id and vice versa to optimize compatibility
with third party packages as these often assume 'id' to be present in the query results.



## Migrations
Existing Illuminate based migration files can be quickly converted to use the Aranguent Schema Builder with the following 
Artisan command: 

```php
 php artisan migrations:convert; 
 ```

As ArangoDB is schemaless, column creation methods are ignored. However, index creation on a column is caught and handled: 

`$table->string('email')->unique()` creates a unique skiplist index for the 'email' attribute.

The `->autoIncrement()` column modifier and `$table->increments('id');` column definition will set autoincrement on the 
collections _id if used during collection creation. This is solely for developer expectations management.  

### Indexes
Index creation defaults to skiplist indexes. If you solely need exact matching you may want to change your index 
to a hash index as these provide better performance.

Note that each document in ArangoDB has two primary keys with hash indexes bij default: _key and _id ({collection}/{_key})

`renameIndex()` is ignored as indexes are unnamed in ArangoDB.

`dropIndex` needs to provide the type and attribute(s) of the index in order to work.
`dropPrimary` is ignored, you can't drop the primary indexes in ArangoDB.
`dropUnique` and `dropSpatialIndex` need to be rewritten to dropIndex.

## Raw SQL queries
You will need to convert any raw SQL queries to AQL. 
The ArangoDB documentation provides examples for common equivalents between the two languages.

## Transactions
Transactions work differently now so check out the documentation to see how you can use them.

