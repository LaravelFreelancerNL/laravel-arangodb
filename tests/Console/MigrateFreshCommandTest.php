<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('migrate:fresh', function () {
    $path = [
        realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
    ];

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/'),
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,

    ])->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);
    expect(count($collections))->toBe($this->tableCount);
});

test('migrate:fresh --database=arangodb', function () {
    $path = [
        realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
    ];

    $this->artisan('migrate:fresh', [
        '--database' => 'arangodb',
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/'),
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,

    ])->assertExitCode(0);

    $collections = $this->schemaManager->getCollections(true);
    expect(count($collections))->toBe($this->tableCount);
});

test('migrate:fresh --database=none', function () {
    $this->artisan('migrate:fresh', [
        '--database' => 'none',
    ])->assertExitCode(0);
})->throws(InvalidArgumentException::class);

test('migrate:fresh --drop-views', function () {
    $path = [
        realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
    ];

    if (!$this->schemaManager->hasView('dropViewTest')) {
        Schema::createView('dropViewTest', []);
    }

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/'),
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,
        '--drop-views' => true,

    ])->assertExitCode(0);

    $views = $this->schemaManager->getViews();
    expect(count($views))->toBe(2);
});

test('migrate:fresh --drop-analyzers', function () {
    $initialAnalyzers = $this->schemaManager->getAnalyzers();

    $path = [
        realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
    ];

    if (!$this->schemaManager->hasAnalyzer('dropMyAnalyzer')) {
        Schema::createAnalyzer(
            'dropMyAnalyzer',
            'identity',
        );
    }


    $analyzersAfterCreation = $this->schemaManager->getAnalyzers();

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/'),
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,
        '--drop-analyzers' => true,

    ])->assertExitCode(0);

    $endAnalyzers = $this->schemaManager->getAnalyzers();

    expect(count($analyzersAfterCreation))->toBe(1 + count($initialAnalyzers));
    expect(count($initialAnalyzers))->toBe(count($endAnalyzers));
});

test('migrate:fresh --drop-graphs', function () {
    $path = [
        realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
    ];

    if (!$this->schemaManager->hasGraph('dropMyGraph')) {
        Schema::createGraph(
            'dropMyGraph',
            [
                'edgeDefinitions' => [
                    [
                        'collection' => 'children',
                        'from' => ['characters'],
                        'to' => ['characters'],
                    ],
                ],
            ],
            true,
        );
    }

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/'),
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,
        '--drop-graphs' => true,

    ])->assertExitCode(0);

    $graphs = $this->schemaManager->getGraphs();
    expect(count($graphs))->toBe(0);
});

test('migrate:fresh --drop-all', function () {
    $initialViews = $this->schemaManager->getViews();
    $initialAnalyzers = $this->schemaManager->getAnalyzers();
    $initialGraphs = $this->schemaManager->getGraphs();

    $path = [
        realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
    ];

    if (!$this->schemaManager->hasAnalyzer('dropMyAnalyzer')) {
        Schema::createAnalyzer(
            'dropMyAnalyzer',
            'identity',
        );
    }
    if (!$this->schemaManager->hasGraph('dropMyGraph')) {
        Schema::createGraph(
            'dropMyGraph',
            [
                'edgeDefinitions' => [
                    [
                        'collection' => 'children',
                        'from' => ['characters'],
                        'to' => ['characters'],
                    ],
                ],
            ],
            true,
        );
    }

    if (!$this->schemaManager->hasView('dropViewTest')) {
        Schema::createView('dropViewTest', []);
    }

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/'),
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,
        '--drop-all' => true,

    ])->assertExitCode(0);

    $endAnalyzers = $this->schemaManager->getAnalyzers();
    $endGraphs = $this->schemaManager->getGraphs();
    $endViews = $this->schemaManager->getViews();

    expect(count($initialAnalyzers))->toBe(count($endAnalyzers));
    expect(count($initialGraphs))->toBe(count($endGraphs));
    expect(count($initialViews))->toBe(count($endViews));

});

test('migrate:fresh --drop-types', function () {
    $path = [
        realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
    ];

    $this->artisan('migrate:fresh', [
        '--path' => [
            database_path('migrations'),
            realpath(__DIR__ . '/../../TestSetup/Database/Migrations'),
            realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/migrations/'),
        ],
        '--realpath' => true,
        '--seed' => true,
        '--seeder' => DatabaseSeeder::class,
        '--drop-types' => true,

    ])->assertExitCode(0);
})->throws('This database driver does not support dropping all types.');
