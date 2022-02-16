<?php

uses(TestCase::class);

test('associative flatten with nested data', function () {
    $data = [
        "_key" => "NedStark",
        "name" => "Ned",
        "surname" => "Stark",
        "alive" => true,
        "age" => 41,
        "residence_id" => "locations/winterfell",
        "en" => [
            "description" => "
                Lord Eddard Stark, also known as Ned Stark, was the head of House Stark, the Lord of Winterfell, 
                Lord Paramount and Warden of the North, and later Hand of the King to King Robert I Baratheon. 
                He was the older brother of Benjen, Lyanna and the younger brother of Brandon Stark. He is the 
                father of Robb, Sansa, Arya, Bran, and Rickon by his wife, Catelyn Tully, and uncle of Jon Snow, 
                who he raised as his bastard son. He was a dedicated husband and father, a loyal friend, 
                and an honorable lord.",
            "quotes" => [
                "When the snows fall and the white winds blow, the lone wolf dies, but the pack survives."
            ],
        ],
    ];

    $result = associativeFlatten($data);

    $this->assertArrayNotHasKey('en', $result);
    $this->assertArrayHasKey('en.description', $result);
    $this->assertArrayHasKey('en.quotes', $result);
    expect($result['en.quotes'])->toBeArray();
});

test('associative flatten with list', function () {
    $data = [
        '_key'    => 'RobertBaratheon',
        'name'    => 'Robert',
        'surname' => 'Baratheon',
        'alive'   => false,
        'age'     => null,
        'traits'  => ['A', 'H', 'C'],
    ];

    $result = associativeFlatten($data);

    expect($result)->toBe($data);
});
