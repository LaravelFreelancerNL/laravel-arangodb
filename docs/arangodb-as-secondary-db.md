# Using ArangoDB as your secondary database
You may want to use ArangoDB as a secondary database. You might want to use its search functionality, schemaless structure for logging or its
Graph functionality for more advanced relationships.

Whatever the reason, this driver has a few features to support different types of databases at once.

Laravel by default is geared to the SQL databases such as MySql, PostgreSQL etc.
This driver aims at providing a drop-in replacement as far as is possible. 

While there are some incompatibilities the general user shouldn't have to think about
using a prefix for well known Artisan commands such as make:model or migrate for example.

## Artisan commands
By default, Artisan commands are overriden and a default ArangoDB connection is assumed. 

For migrate:x commands you may additionally specify the database connection through 
the --database=[connection-name] argument.
If ArangoDB is NOT your default connection you use --database=arangodb.

For make:model and make:migration you add the --arangodb argument to use the features
this driver supplies. This is only necessary if your default connection is not ArangoDB.
Otherwise, they'll fall back to their Illuminate parent command.

## Eloquent & the QueryBuilder
You can set a connection on your models to link them to a specific connection, and its builders.
When using the DB facade make sure you import the right one.