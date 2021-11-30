<?php

namespace Tests\Query;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Database\Seeds\CharactersSeeder;
use Tests\Setup\Models\Character;
use Tests\TestCase;

class IdKeyConversionTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');

        Artisan::call('db:seed', ['--class' => CharactersSeeder::class]);
    }

    public function testOutputConversionWithWholeDocument()
    {
        $result = DB::table('characters')->get();

        $this->assertObjectHasAttribute('id', $result->first());
    }

    public function testOutputConversionWithLimitedAttributes()
    {
        $result = DB::table('characters')->get(['_id', '_key', 'name']);

        $this->assertObjectHasAttribute('id', $result->first());
        $this->assertCount(3, (array)$result->first());
    }

    public function testOutputConversionWithoutKey()
    {
        $result = DB::table('characters')->get(['_id','name']);

        $this->assertObjectNotHasAttribute('id', $result->first());
        $this->assertObjectHasAttribute('_id', $result->first());
        $this->assertCount(2, (array)$result->first());
    }

    public function testGetIdConversionSingleAttribute()
    {
        $builder = $this->getBuilder();
        $builder = $builder->select('id')->from('users');

        $this->assertSame(
            'FOR userDoc IN users RETURN userDoc._key',
            $builder->toSql()
        );
    }

    public function testGetIdConversionMultipleAttributed()
    {
        $query = DB::table('characters')->select('id', 'name');

        $results = $query->get();

        $this->assertSame(
            'FOR characterDoc IN characters RETURN {"id":characterDoc._key,"name":characterDoc.name}',
            $query->toSql()
        );
        $this->assertObjectNotHasAttribute('_key', $results->first());
        $this->assertObjectHasAttribute('id', $results->first());
        $this->assertCount(2, (array)$results->first());
    }

    public function testGetIdConversionWithAlias()
    {
        $query = DB::table('characters')->select('id as i', 'name');

        $results = $query->get();

        $this->assertSame(
            'FOR characterDoc IN characters RETURN {"i":characterDoc._key,"name":characterDoc.name}',
            $query->toSql()
        );
        $this->assertObjectNotHasAttribute('_key', $results->first());
        $this->assertObjectNotHasAttribute('id', $results->first());
        $this->assertObjectHasAttribute('i', $results->first());
        $this->assertCount(2, (array)$results->first());
    }

    public function testGetIdConversionWithMultipleIds()
    {
        $query = DB::table('characters')->select('id', 'id as i', 'name');

        $results = $query->get();

        $this->assertSame(
            'FOR characterDoc IN characters RETURN {"id":characterDoc._key,"i":characterDoc._key,"name":characterDoc.name}',
            $query->toSql()
        );
        $this->assertObjectNotHasAttribute('_key', $results->first());
        $this->assertObjectHasAttribute('id', $results->first());
        $this->assertObjectHasAttribute('i', $results->first());
        $this->assertCount(3, (array)$results->first());
    }

    public function testGetIdConversionWithMultipleAliases()
    {
        $query = DB::table('characters')->select('id as i', 'id as i2', 'name');

        $results = $query->get();

        $this->assertSame(
            'FOR characterDoc IN characters RETURN {"i":characterDoc._key,"i2":characterDoc._key,"name":characterDoc.name}',
            $query->toSql()
        );
        $this->assertObjectNotHasAttribute('_key', $results->first());
        $this->assertObjectNotHasAttribute('id', $results->first());
        $this->assertObjectHasAttribute('i', $results->first());
        $this->assertObjectHasAttribute('i2', $results->first());
        $this->assertCount(3, (array)$results->first());
    }

    public function testGetIdConversionWithWheres()
    {
        $query = DB::table('characters')
            ->where('id', 'NedStark');

        $results = $query->get();

        $this->assertSame(
            'FOR characterDoc IN characters FILTER characterDoc._key == @' . $query->aqb->getQueryId() . '_1',
            $query->toSql()
        );

        $this->assertObjectNotHasAttribute('_key', $results->first());
        $this->assertObjectHasAttribute('id', $results->first());
        $this->assertSame('NedStark', $results->first()->id);
        $this->assertCount(1, $results);
    }


    public function testModelHasCorrectIds()
    {
        $results = Character::all();

        $this->assertSame('NedStark', $results->first()->id);
        $this->assertSame('characters/NedStark', $results->first()->_id);
        $this->assertNull($results->first()->_key);
    }
}
