<?php

declare(strict_types=1);

use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;

beforeEach(function () {
    $this->schemaManager = $this->connection->getArangoClient()->schema();
});

test('attribute ignore additional arguments', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->string('token', 64)->index();

        $commands = $table->getCommands();
        expect(count($commands))->toEqual(2);
        expect(count($commands[1]['columns']))->toEqual(1);
    });
});

test('foreign id is excluded', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->foreignId('user_id')->index();

        $commands = $table->getCommands();

        expect(count($commands))->toEqual(2);
        expect($commands[0]['name'])->toEqual('ignore');
        expect($commands[0]['method'])->toEqual('foreignId');
        expect($commands[1]['columns'][0])->toEqual('user_id');
    });
});

test('default', function () {
    Schema::table('characters', function (Blueprint $table) {
        $table->string('name')->default('John Doe');

        $commands = $table->getCommands();

        expect(count($commands))->toEqual(2);
        expect($commands[0]['name'])->toEqual('ignore');
        expect($commands[0]['method'])->toEqual('string');
        expect($commands[1]['name'])->toEqual('ignore');
        expect($commands[1]['method'])->toEqual('default');
    });
});