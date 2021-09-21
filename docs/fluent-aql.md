# Fluent AQL

[Fluent AQL](https://github.com/LaravelFreelancerNL/fluentaql) is the query builder that powers the Eloquent conversions to AQL. So you can run most Eloquent commands
as normal.

AQL is a lot mor powerful however and there may be cases where you want to use its power directly. To make this easier
you can use Fluent AQL:

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

Alternatively; if you want to cast the results to a model you can replace step 3 with:
```php
// 3: execute the query on the connection AND cast the results to the model
    $results = YourModel::execute($aqb);
```
The query will be executed and the results automatically cast to an Eloquent collection of YourModel instances.
