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
        $locations = '[
           {
              "_key":"dragonstone",
              "name":"Dragonstone",
              "coordinate":[
                 55.167801,
                 -6.815096
              ],
              "led_by":"DaenerysTargaryen",
              "capturable_id":"DaenerysTargaryen",
              "capturable_type": "Tests\\\Setup\\\Models\\\Character"
           },
           {
              "_key":"king-s-landing",
              "name":"King\'s Landing",
              "coordinate":[
                 42.639752,
                 18.110189
              ],
              "led_by":"CerseiLannister",
              "capturable_id":"DaenerysTargaryen",
              "capturable_type": "Tests\\\Setup\\\Models\\\Character"
           },
           {
              "_key":"the-red-keep",
              "name":"The Red Keep",
              "coordinate":[
                 35.896447,
                 14.446442
              ],
              "led_by":"CerseiLannister",
              "capturable_id":"DaenerysTargaryen",
              "capturable_type": "Tests\\\Setup\\\Models\\\Character"

           },
           {
              "_key":"yunkai",
              "name":"Yunkai",
              "coordinate":[
                 31.046642,
                 -7.129532
              ],
              "led_by":"DaenerysTargaryen",
              "capturable_id":"DaenerysTargaryen",
              "capturable_type": "Tests\\\Setup\\\Models\\\Character"

           },
           {
              "_key":"astapor",
              "name":"Astapor",
              "coordinate":[
                 31.50974,
                 -9.774249
              ],
              "led_by":"DaenerysTargaryen",
              "capturable_id":"DaenerysTargaryen",
              "capturable_type": "Tests\\\Setup\\\Models\\\Character"

           },
           {
              "_key":"winterfell",
              "name":"Winterfell",
              "coordinate":[
                 54.368321,
                 -5.581312
              ],
              "led_by":"SansaStark",
              "capturable_id":"TheonGreyjoy",
              "capturable_type": "Tests\\\Setup\\\Models\\\Character"

           },
           {
              "_key":"vaes-dothrak",
              "name":"Vaes Dothrak",
              "coordinate":[
                 54.16776,
                 -6.096125
              ],
              "led_by":"DaenerysTargaryen"
           },
           {
              "_key":"beyond-the-wall",
              "name":"Beyond the wall",
              "coordinate":[
                 64.265473,
                 -21.094093
              ]
           },
           {
              "_key":"riverrun",
              "name":"Riverrun",
              "coordinate":[
                54.311011,
                -6.5214502
              ]
           }
        ]';

        $locations = json_decode($locations, JSON_OBJECT_AS_ARRAY);

        foreach ($locations as $location) {
            Location::insertOrIgnore($location);
        }
    }
}
