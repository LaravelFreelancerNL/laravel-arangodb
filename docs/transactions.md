# Transactions
The basic API for transactions is the same, however there is an important difference for write actions:

**Collections on which you perform any kind of write action _must_ be declared at the start of a transaction.**

## Starting a transaction
As a closure:
```php
DB::transaction(function () {
    DB::insert('FOR i IN 1..10 INSERT { _key: CONCAT("test", i) } INTO characters');
    DB::delete('FOR c IN characters FILTER c._key == "test10" REMOVE c IN characters');
}, 5, ['write' => ['characters']]);
```
**note:** the number of retries must be explicitly set when registering collections 

Manually:
```php
DB::beginTransaction([
    'write' => [
        'characters',
         'locations'
    ]
]);
```

### Registering collections
$collections must be an array that can have zero or any of the keys: read, write or exclusive, 
each being an array of collection names or a single collection name as string.

Collections that will be written to in the transaction _must_ be declared with the write or exclusive attribute
or it will fail, whereas non-declared collections from which is solely read will be added lazily.
[More information on locking and isolation in ArangoDB](https://www.arangodb.com/docs/stable/transactions-locking-and-isolation.html).

## Commiting a transaction
As normal:
```php
DB::commit();
```
## Rolling back a transaction
As normal:
```php
DB::rollBack();
```
