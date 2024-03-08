<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaravelFreelancerNL\Aranguent\Connection;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;
use Tests\Setup\ClassStubs\CustomBlueprint;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

test('create with custom blueprint', function () {
    $schema = DB::connection()->getSchemaBuilder();

    $grammar = DB::connection()->getSchemaGrammar();
    $schemaManager = DB::connection()->getArangoClient()->schema();

    $schema->blueprintResolver(function ($table, $grammar, $schemaManager, $callback) {
        return new CustomBlueprint($table, $grammar, $schemaManager, $callback);
    });

    $schema->create('characters', function (Blueprint $table) {
        expect($table)->toBeInstanceOf(CustomBlueprint::class);
    });

    refreshDatabase();
});

test('getConnection', function () {
    expect(Schema::getConnection())->toBeInstanceOf(Connection::class);
});

test('Silently fails unsupported functions', function () {
    Schema::nonExistingFunction('none-existing-analyzer');
})->throwsNoExceptions();

test('Unsupported functions are logged', function () {
    LogFake::bind();

    Schema::nonExistingFunction('none-existing-analyzer');

    Log::assertLogged(
        fn(LogEntry $log) => $log->level === 'warning'
    );
});
