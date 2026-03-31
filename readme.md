Aranguent

[ArangoDB](https://www.arangodb.com) driver for [Laravel](https://laravel.com)  
<sub>The unguent between the ArangoDB and Laravel</sub>
</p
    

---------
# I’m archiving my ArangoDB PHP/Laravel packages

Due to the license changes ArangoDB introduced last year, it no longer makes sense for me to continue using the product or to invest further time in developing, maintaining, and improving these packages.

While building and running side projects is in many ways easier and more affordable than ever, the new license creates a significant barrier for my own projects.

I’ve genuinely enjoyed working with ArangoDB, and I still believe it is an excellent product. However, under the current licensing model, I can no longer justify the time required to support these packages. Time is my most limited resource, and I need to allocate it where it makes sense professionally.

As a result, I am archiving the following packages:

- The Laravel driver: https://github.com/LaravelFreelancerNL/laravel-arangodb
- The PHP client: https://github.com/LaravelFreelancerNL/arangodb-php-client
- The AQL query builder: https://github.com/LaravelFreelancerNL/fluentaql

If there is interest in continuing their development, you are welcome to fork them and maintain your own versions. Alternatively, if you would like to sponsor or hire me to continue maintaining them, please feel free to get in touch.

Thank you to everyone who has used, supported, or contributed to these packages.

So long, and thanks for all the fish.

Bas  
Laravel Freelancer NL
---------


The goal is to create a drop-in ArangoDB replacement for Laravel's database, migrations and model handling.

**This package is in development; use at your own peril.**

## Installation
This driver is currently in the v1 beta stage.
To install it make sure that the minimum stability is 
set to beta or lower, and that prefer-stable is set to false in composer.json:

```
    "minimum-stability": "beta",
    "prefer-stable": false,
```

You may then use composer to install Aranguent:

``` composer require laravel-freelancer-nl/aranguent ```



### Version compatibility
| Laravel       | ArangoDB | PHP  | Aranguent                |
|:--------------|:---------|:-----|:-------------------------|
| ^8.0 and ^9.0 | ^3.7     | ^8.0 | ^0.13                    |
| ^11.0         | ^3.11    | ^8.2 | ^1.0.0 - 1.0.0-beta.11   |
| ^12.0         | ^3.11    | ^8.2 | ^v1.0.0-beta.12          |

## Documentation
1) [Connect to ArangoDB](docs/connect-to-arangodb.md): set up a connection
2) [Converting from SQL databases to ArangoDB](docs/from-sql-to-arangodb.md):
3) [Migrations](docs/migrations.md): migration conversion and commands 
4) [Eloquent relationships](docs/eloquent-relationships.md): supported relationships 
5) [Query Builder](docs/query-functions.md): supported functions
6) [Selecting JSON data](docs/selecting-json-data.md): how to select subsets of documents.
7) [ArangoSearch](docs/arangosearch.md): searching views
8) [Transactions](docs/transactions.md): how to set up ArangoDB transactions
9) [FluentAQL](docs/fluent-aql.md): Use the AQL query builder directly
10) [Testing](docs/testing.md): testing your project with Aranguent.
11) [Compatibility list](docs/compatibility-list.md): overview of DB related compatible methods.
11) [Secondary database](docs/arangodb-as-secondary-db.md): using ArangoDB as your secondary database.

## Related packages
* [ArangoDB PHP client](https://github.com/LaravelFreelancerNL/arangodb-php-client)
* [FluentAQL - AQL Query Builder](https://github.com/LaravelFreelancerNL/fluentaql)
