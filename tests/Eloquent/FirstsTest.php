<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use LaravelFreelancerNL\Aranguent\Testing\DatabaseTransactions;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;

uses(
    DatabaseTransactions::class
);

test('first', function () {
    $ned = Character::first();

    expect($ned->id)->toEqual('NedStark');
});

test('first with columns', function () {
    $ned = Character::first(['_id', 'name']);

    expect($ned->name)->toEqual('Ned');
    expect($ned->toArray())->toHaveCount(2);
});

test('first or', function () {
    $ned = Character::firstOr(function () {
        return false;
    });

    expect($ned->name)->toEqual('Ned');
});

test('first or trigger or', function () {
    $location = Location::where('id', 'free-cities')->firstOr(function () {
        return false;
    });

    expect($location)->toBeFalse();
});

test('first or create', function () {
    $ned = Character::find('NedStark');

    $char = [
        '_key' => 'NedStark',
        'name' => 'Ned',
        'surname' => 'Stark',
        'alive' => true,
        'age' => 41,
        'residence_id' => 'winterfell',
    ];

    $model = Character::firstOrCreate($char);

    expect($model->_id)->toBe($ned->_id);
    expect($model->id)->toBe($ned->id);
});

test('firstOrCreate with nested data', function () {
    $char = [
        '_key' => 'NedStark',
        'name' => 'Ned',
        'surname' => 'Stark',
        'alive' => true,
        'age' => 41,
        'residence_id' => 'winterfell',
        'en' => [
            'description' => '
                Lord Eddard Stark, also known as Ned Stark, was the head of House Stark, the Lord of Winterfell, 
                Lord Paramount and Warden of the North, and later Hand of the King to King Robert I Baratheon. 
                He was the older brother of Benjen, Lyanna and the younger brother of Brandon Stark. He is the 
                father of Robb, Sansa, Arya, Bran, and Rickon by his wife, Catelyn Tully, and uncle of Jon Snow, 
                who he raised as his bastard son. He was a dedicated husband and father, a loyal friend, 
                and an honorable lord.',
            'quotes' => [
                'When the snows fall and the white winds blow, the lone wolf dies, but the pack survives.',
            ],
        ],
    ];

    $ned = Character::find('NedStark');
    $ned->en = $char['en'];
    $ned->save();

    $model = Character::firstOrCreate(
        $char,
        $char
    );

    expect($model->_id)->toBe($ned->_id);
    expect($model->en->description)->toBe($char['en']['description']);
    expect($model->en->quotes)->toBe($char['en']['quotes']);
});

test('first or fail', function () {
    $ned = Character::firstOrFail();

    expect($ned->id)->toEqual('NedStark');
});

test('first or failing', function () {
    Location::where('id', 'free-cities')->firstOrFail();
})->throws(ModelNotFoundException::class);

test('first or new', function () {
    $ned = Character::find('NedStark');

    $char = [
        '_key' => 'NedStark',
        'name' => 'Ned',
        'surname' => 'Stark',
        'alive' => true,
        'age' => 41,
        'residence_id' => 'winterfell',
    ];

    $model = Character::firstOrNew($char);

    expect($model->id)->toBe($ned->id);
});

test('first or new none existing', function ($character) {
    $edmure = Character::find('EdmureTully');

    $model = Character::firstOrNew($character);

    expect($edmure)->toBeNull();
    $this->assertFalse(property_exists($model, 'id'));
})->with('character');

test('first where', function () {
    $result = Character::firstWhere('name', 'Jorah');

    expect($result)->toBeInstanceOf(Character::class);
    expect($result->name)->toBe('Jorah');
});
