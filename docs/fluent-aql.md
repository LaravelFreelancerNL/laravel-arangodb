# Fluent AQL

[Fluent AQL](https://github.com/LaravelFreelancerNL/fluentaql) is the query builder that powers the Eloquent conversions to AQL. So you can run most Eloquent commands
as normal.

AQL is a lot more powerful however and there may be cases where you want to use its power directly. To make this easier
you can use Fluent AQL:

## Executing a query builder on the current connection:
```php
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

// 1: Start up a new query builder:
    $aqb = new QueryBuilder();
    
// 2: set your query
    $aqb->for('characterDoc', 'characters')
        ->return('characterDoc');

// 3: execute the query on the connection
    \DB::execute($aqb);
```