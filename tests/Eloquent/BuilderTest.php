<?php

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\TestCase;

uses(
    TestCase::class,
    DatabaseTransactions::class
);
beforeEach(function () {
    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    Carbon::setTestNow(null);
    Carbon::resetToStringFormat();
    Model::unsetEventDispatcher();

    M::close();
});

test('insert handles array attributes', function ($character) {
    Character::insert($character);

    $characterModel = Character::find($character['id']);

    expect($characterModel->id)->toEqual('EdmureTully');
    expect($characterModel->en)->toBeObject();
    expect((array) $characterModel->en)->toEqual($character['en']);
    expect($characterModel)->toBeInstanceOf(Character::class);
})->with('character');

test('update sets correct updated at', function ($character) {
    Character::insert($character);

    $retrievedBeforeUpdate = Character::find($character['id']);
    $retrievedBeforeUpdate->update(['alive' => false]);

    $retrievedAfterUpdate = Character::find($character['id']);
    $this->assertArrayNotHasKey('characters.updated_at', $retrievedAfterUpdate->toArray());
})->with('character');
