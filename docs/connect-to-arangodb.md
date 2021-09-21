#Set up a connection to ArangoDB
1) Add a new database connection to `config/database.php`

```php
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

2) Set your default connection `DB_CONNECTION=arangodb` in your .env file.

## Connect to docker
To connect to the default ArangoDB docker image you need to set your DB_ENDPOINT to 'tcp://arangodb:8529'.
