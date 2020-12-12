<?php

namespace Tests\Setup\Database\Seeds;

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
              "tag_key":"A",
              "taggable_id":"winterfell",
              "taggable_type":"Tests\\\Setup\\\Models\\\Location"
           },
          {
              "tag_key":"S",
              "taggable_id":"beyond-the-wall",
              "taggable_type":"Tests\\\Setup\\\Models\\\Location"
           },
          {
              "tag_key":"E",
              "taggable_id":"PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_key":"F",
              "taggable_id":"PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_key":"G",
              "taggable_id":"PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_key":"A",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_key":"F",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_key":"K",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_key":"P",
              "taggable_id":"SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           }
        ]';

        $taggables = json_decode($taggables, JSON_OBJECT_AS_ARRAY);

        foreach ($taggables as $taggable) {
            Taggable::insert($taggable);
        }
    }
}
