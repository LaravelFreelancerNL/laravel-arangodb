<?php

namespace Tests\Setup\Database\Seeds;

use Illuminate\Database\Seeder;
use Tests\Setup\Models\Location;

class HousesSeeder extends Seeder
{
    /**
     * Run the database Seeds.
     *
     * @return void
     */
    public function run()
    {
        $houses = '[
           {
              "_key":"targaryan",
              "name":"Targaryan",
              "location_id":"dragonstone"
           },
           {
              "_key":"stark",
              "name":"Stark",
              "location_id":"winterfell"
           },
           {
              "_key":"lannister",
              "name":"Lannister",
              "location_id":"king-s-landing"
           }
        ]';

        $houses = json_decode($houses, JSON_OBJECT_AS_ARRAY);

        foreach ($houses as $house) {
            Location::insert($house);
        }
    }
}
