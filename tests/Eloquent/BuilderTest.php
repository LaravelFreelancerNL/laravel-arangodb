<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow(null);
        Carbon::resetToStringFormat();
        Model::unsetEventDispatcher();
        M::close();
    }

    public function testInsertHandlesArrayAttributes()
    {
        $character = [
            'en' => [
                'titles' => ['Lord of Winterfell', 'Hand of the king'],
            ],
            '_key'         => 'NedStark',
            'name'         => 'Ned',
            'surname'      => 'Stark',
            'alive'        => false,
            'age'          => 41,
            'location_key' => 'kingslanding',
        ];
        Character::insert($character);

        $ned = Character::find('NedStark');

        $this->assertEquals('NedStark', $ned->_key);
        $this->assertIsObject($ned->en);
        $this->assertEquals($character['en'], (array) $ned->en);
        $this->assertInstanceOf(Character::class, $ned);
    }

    public function testUpdateSetsCorrectUpdatedAt()
    {
        $character = [
            'en' => [
                'titles' => ['Lord of Winterfell', 'Hand of the king'],
            ],
            '_key'         => 'NedStark',
            'name'         => 'Ned',
            'surname'      => 'Stark',
            'alive'        => true,
            'age'          => 41,
            'location_key' => 'kingslanding',
        ];
        Character::insert($character);
        $retrievedBeforeUpdate = Character::find('NedStark');
        $retrievedBeforeUpdate->update(['alive' => false]);

        $retrievedAfterUpdate = Character::find('NedStark');
        $this->assertArrayNotHasKey('characters.updated_at', $retrievedAfterUpdate->toArray());
    }
}
