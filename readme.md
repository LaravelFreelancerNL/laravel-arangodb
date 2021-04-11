Aranguent
---------
<p align="center">

![Github CI tests](https://github.com/LaravelFreelancerNL/laravel-arangodb/workflows/CI%20tests/badge.svg)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/LaravelFreelancerNL/laravel-arangodb/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/LaravelFreelancerNL/laravel-arangodb/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/LaravelFreelancerNL/laravel-arangodb/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/LaravelFreelancerNL/laravel-arangodb/?b=master)
<a href="https://packagist.org/packages/laravel-freelancer-nl/aranguent"><img src="https://poser.pugx.org/laravel-freelancer-nl/aranguent/v/unstable" alt="Latest Version"></a>
<a href="https://packagist.org/packages/laravel-freelancer-nl/aranguent"><img src="https://poser.pugx.org/laravel-freelancer-nl/aranguent/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel-freelancer-nl/aranguent"><img src="https://poser.pugx.org/laravel-freelancer-nl/aranguent/license" alt="License"></a>

[ArangoDB](https://www.arangodb.com) driver for [Laravel](https://laravel.com)  
<sub>The unguent between the ArangoDB and Laravel</sub>
</p>

The goal is to create a drop-in ArangoDB replacement for Laravel's database, migrations and model handling.

**This package is in development; use at your own peril.**

## Installation
You may use composer to install Aranguent:

``` composer require laravel-freelancer-nl/aranguent ```

### Version compatibility
| Laravel | ArangoDB | PHP       | Aranguent |
| :------ | :------- | :-------- | :-------- |
| ^8.0    | ^3.6     | ^7.4&#124;^8.0 | ^0.7      |

## Documentation
1) [Connect to ArangoDB](docs/connect-to-arangodb.md): set up a connection
2) [Converting from a SQL database to ArangoDB](docs/from-sql-to-arangodb.md):
3) [Migrations](docs/migrations.md): migration conversion and commands 
4) [Eloquent relationships](docs/eloquent-relationships.md): supported relationships 
5) [Query Builder](docs/query-functions.md): supported functions 
6) [Transactions](docs/transactions.md): how to set up ArangoDB transactions
