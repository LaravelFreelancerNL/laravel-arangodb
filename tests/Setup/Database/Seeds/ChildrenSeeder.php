<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Tests\Setup\Models\Child;

class ChildrenSeeder extends Seeder
{
    /**
     * Run the database Seeds.
     *
     * @return void
     */
    public function run()
    {
        $children = '[
           {
              "_from":"characters/NedStark",
              "_to":"characters/RobbStark"
           },
           {
              "_from":"characters/NedStark",
              "_to":"characters/SansaStark"
           },
           {
              "_from":"characters/NedStark",
              "_to":"characters/AryaStark"
           },
           {
              "_from":"characters/NedStark",
              "_to":"characters/BranStark"
           },
           {
              "_from":"characters/CatelynStark",
              "_to":"characters/RobbStark"
           },
           {
              "_from":"characters/CatelynStark",
              "_to":"characters/SansaStark"
           },
           {
              "_from":"characters/CatelynStark",
              "_to":"characters/AryaStark"
           },
           {
              "_from":"characters/CatelynStark",
              "_to":"characters/BranStark"
           },
           {
              "_from":"characters/NedStark",
              "_to":"characters/JonSnow"
           },
           {
              "_from":"characters/TywinLannister",
              "_to":"characters/JaimeLannister"
           },
           {
              "_from":"characters/TywinLannister",
              "_to":"characters/CerseiLannister"
           },
           {
              "_from":"characters/TywinLannister",
              "_to":"characters/TyrionLannister"
           },
           {
              "_from":"characters/CerseiLannister",
              "_to":"characters/JoffreyBaratheon"
           },
           {
              "_from":"characters/JaimeLannister",
              "_to":"characters/JoffreyBaratheon"
           }
        ]';

        $childOf = json_decode($children, JSON_OBJECT_AS_ARRAY);
        foreach ($childOf as $relation) {
            Child::insertOrIgnore($relation);
        }
    }
}
