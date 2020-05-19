<?php

namespace Tests\Setup\Database\Seeds;

use Illuminate\Database\Seeder;
use Tests\Setup\Models\Location;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database Seeds.
     *
     * @return void
     */
    public function run()
    {
        $locations = `[
            { "_key": "dragonstone", "name": "Dragonstone", "coordinate": [ 55.167801, -6.815096 ] },
            { "_key": "king-s-landing", "name": "King's Landing", "coordinate": [ 42.639752, 18.110189 ] },
            { "_key": "the-red-keep", "name": "The Red Keep", "coordinate": [ 35.896447, 14.446442 ] },
            { "_key": "yunkai", "name": "Yunkai", "coordinate": [ 31.046642, -7.129532 ] },
            { "_key": "astapor", "name": "Astapor", "coordinate": [ 31.50974, -9.774249 ] },
            { "_key": "winterfell", "name": "Winterfell", "coordinate": [ 54.368321, -5.581312 ] },
            { "_key": "vaes-dothrak", "name": "Vaes Dothrak", "coordinate": [ 54.16776, -6.096125 ] },
            { "_key": "beyond-the-wall", "name": "Beyond the wall", "coordinate": [ 64.265473, -21.094093 ] }
        ]`;

        $locations = json_decode($locations);
        Location::insertOrupdate($locations);
    }
}
