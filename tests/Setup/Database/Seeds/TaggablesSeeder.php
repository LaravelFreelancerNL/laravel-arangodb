<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Tests\Setup\Models\Taggable;

class TaggablesSeeder extends Seeder
{
    /**
     * Run the database Seeds.
     *
     * @return void
     */
    public function run()
    {
        $taggables = '[
           {
              "tag_id":"A",
              "taggable_id":"winterfell",
              "taggable_type":"Tests\\\Setup\\\Models\\\Location"
           },
          {
              "tag_id":"S",
              "taggable_id":"beyond-the-wall",
              "taggable_type":"Tests\\\Setup\\\Models\\\Location"
           },
          {
              "tag_id":"E",
              "taggable_id":"PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"F",
              "taggable_id":"PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"G",
              "taggable_id":"PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"A",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"F",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"K",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"P",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           }
        ]';

        $taggables = json_decode($taggables, JSON_OBJECT_AS_ARRAY);

        foreach ($taggables as $taggable) {
            Taggable::insertOrIgnore($taggable);
        }
    }
}
