<?php

namespace Tests\Query;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use Tests\TestCase;

class GroupByTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => \Tests\Setup\Database\Seeds\CharactersSeeder::class]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        M::close();
    }

    public function testGroupBy()
    {
        $surnames = DB::table('characters')
            ->select('surname')
            ->groupBy('surname')
            ->get();

        $this->assertCount(21, $surnames);
        $this->assertEquals('Baelish', $surnames[1]);
    }

    public function testGroupByMultiple()
    {
        $groups = DB::table('characters')
            ->select('surname', 'residence_key')
            ->groupBy('surname', 'residence_key')
            ->get();

        $this->assertCount(29, $groups);
        $this->assertEquals(null, $groups[4][0]);
        $this->assertEquals('the-red-keep', $groups[4][1]);
        $this->assertEquals('Baelish', $groups[5][0]);
        $this->assertEquals('the-red-keep', $groups[5][1]);
    }

    public function testHaving()
    {
        $surnames = DB::table('characters')
            ->select('surname')
            ->groupBy('surname')
            ->having('surname', 'LIKE', '%S%')
            ->get();

        $this->assertCount(4, $surnames);
        $this->assertEquals('Seaworth', $surnames[1]);
    }

    public function testOrHaving()
    {
        $ages = DB::table('characters')
            ->select('age')
            ->groupBy('age')
            ->having('age', '<', 20)
            ->orHaving('age', '>', 40)
            ->get();

        $this->assertCount(9, $ages);
        $this->assertEquals(10, $ages[1]);
    }

    public function testHavingBetween()
    {
        $ages = DB::table('characters')
            ->select('age')
            ->groupBy('age')
            ->havingBetween('age', [20, 40])
            ->get();

        $this->assertCount(3, $ages);
        $this->assertEquals(36, $ages[1]);
    }
}
