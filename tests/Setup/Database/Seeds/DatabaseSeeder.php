<?php

namespace Tests\Setup\Database\Seeds;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's test database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CharactersSeeder::class);
        $this->call(ChildrenSeeder::class);
        $this->call(LocationsSeeder::class);
        $this->call(TagsSeeder::class);
        $this->call(TaggablesSeeder::class);
        $this->call(HousesSeeder::class);
    }
}
