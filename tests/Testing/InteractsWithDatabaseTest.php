<?php

declare(strict_types=1);

use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Tag;

uses(
    DatabaseTransactions::class
);

test('assert database has', function () {
    $this->assertDatabaseHas('characters', [
        "id" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => true,
        "age" => 41,
        "residence_id" => "winterfell"
    ]);
});

test('assert database missing', function () {
    $this->assertDatabaseMissing('characters', [
        "id" => "NedStarkIsDead",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => true,
        "age" => 41,
        "residence_id" => "winterfell"
    ]);
});

test('assert database count', function () {
    $this->assertDatabaseCount('characters', 43);
});

test('assert soft deleted by model', function () {
    $strong = Tag::find("A");
    $strong->delete();
    $this->assertSoftDeleted($strong);
});

test('assert soft deleted by data', function () {
    $strong = Tag::find("A");
    $strong->delete();
    $this->assertSoftDeleted('tags', [
        "id" => "A",
        "en" => "strong",
        "de" => "stark"
    ]);
});

test('assert not soft deleted by model', function () {
    $strong = Tag::find("A");
    $this->assertNotSoftDeleted($strong);
});

test('assert not soft deleted by data', function () {
    $strong = Tag::find("A");

    $this->assertNotSoftDeleted('tags', [
        "id" => "A",
        "en" => "strong",
        "de" => "stark"
    ]);
});

test('assert model exists', function () {
    $ned = Character::find('NedStark');
    $this->assertModelExists($ned);
});

test('assert model missing', function () {
    $ned = Character::find('NedStark');

    $ned->delete();

    $this->assertModelMissing($ned);
});

test('cast as json', function () {
    $value = [
        'key' => 'value'
    ];
    $result = $this->castAsJson($value);

    expect($result)->toBeInstanceOf(Expression::class);
});
