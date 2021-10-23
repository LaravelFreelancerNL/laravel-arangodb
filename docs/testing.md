# Testing
You can test your project with phpunit as normal. For database testing there are a few things to consider.

## Transactions
ArangoDB requires that you list all collections (tables) involved in write statements at the start of the transaction.

To enable this you'll have to extend Aranguents TestCase in your own tests and set the collections. Either by setting
the property directly or calling setTransactionCollections from the setUp() method.
```php
use LaravelFreelancerNL\Aranguent\Testing\TestCase as AranguentTestCase;
use LaravelFreelancerNL\Aranguent\Testing\RefreshDatabase;

class MyTest extends AranguentTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->setTransactionCollections([
            'write' => ['pages']
        ]);

        parent::setUp();
    }
    
    public function testSomething()
    {
        // Write something to the database.
    }
}
 ```
**Note**: if you use setUp() to register the collections you MUST do so before calling setUp on the parent.

### Transaction collections
The transactionCollections property consists of an array where the key denotes the locking mode (read, write, exclusive).
and a list of collections for whichever modes are applicable.
 ```php 
 $this->setTransactionCollections([
    'read' => ['collectionA', 'collectionB', 'collectionC'],
    'write' => ['collectionB'],
    'exclusive' => ['collectionX']
]);
```
[See ArangoDB's documentation](https://www.arangodb.com/docs/3.8/transactions-locking-and-isolation.html))

### RefreshDatabase & DatabaseTransactions traits
Aranguent contains an extended version of both the RefreshDatabase & DatabaseTransactions traits that pick up the set
collections:
 ```php 
use \LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use \LaravelFreelancerNL\Aranguent\Testing\RefreshDatabase;
```

## Incompatibilities
There are a few incompatibilities with common Laravel testing practices:

### PestPHP DB writes
You may use PestPHP however the RefreshDatabase & DatabaseTransactions traits are unusable in combination with PestPHP. 

These require that you set the transactionCollections property on Testcase. At the time of writing there is no option
to do so. Because these start transactions before the 'beforeEach' method is called. 
AFAIK this is the only way to reach functions on traits and TestCase.

This means that you either have to set up your own migrations and transactions or just use phpunit.

If you know a solution or want to keep up to date on this issue then check out [this discussion](https://github.com/pestphp/pest/discussions/429)

### Parallel testing
Parallel testing is unsupported at this time.