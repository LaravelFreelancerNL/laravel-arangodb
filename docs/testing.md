# Testing
You can test your project with phpunit as normal. For database testing there are a few things to consider.

## Database assertions
The regular database assertions are supported:

- assertDatabaseHas($table, array $data, $connection = null)
- assertDatabaseMissing($table, array $data, $connection = null)
- assertDatabaseCount($table, int $count, $connection = null)
- assertDeleted($table, array $data = [], $connection = null)
- assertSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
- assertNotSoftDeleted($table, array $data = [], $connection = null, $deletedAtColumn = 'deleted_at')
- assertModelExists($model)
- assertModelMissing($model)

castToJson is support for backwards compatibility. As Json is a first class citizen in ArangoDB this method now just 
returns the given value.

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

### PestPHP DB transactions
ArangoDB requires you to declare the collections written to in a transaction at the start of that transaction.
Within PestPHP test files it is not possible to set this up at the appropriate points in time. You need to do this
in a TestCase and use that in your test files.

The easiest solution is to just register all collections within one TestCase.
For example:
```php
    protected array $transactionCollections = [
        'write' => [
            'failed_jobs',
            'media',
            'mediables',
            'migrations',
            'model_has_permissions',
            'model_has_roles',
            'password_resets',
            'permissions',
            'personal_access_tokens',
            'role_has_permissions',
            'roles',
            'sessions',
            'users',
        ]
    ];
```
It isn't pretty but gets the job done.

