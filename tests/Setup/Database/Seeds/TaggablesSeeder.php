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
              "tag_id":"tags/A",
              "taggable_id":"locations/winterfell",
              "taggable_type":"Tests\\\Setup\\\Models\\\Location"
           },
          {
              "tag_id":"tags/S",
              "taggable_id":"locations/beyond-the-wall",
              "taggable_type":"Tests\\\Setup\\\Models\\\Location"
           },
          {
              "tag_id":"tags/E",
              "taggable_id":"characters/PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"tags/F",
              "taggable_id":"characters/PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"tags/G",
              "taggable_id":"characters/PetyrBaelish",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"tags/A",
              "taggable_id":"characters/SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"tags/F",
              "taggable_id":"characters/SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"tags/K",
              "taggable_id":"characters/SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           },
          {
              "tag_id":"tags/P",
              "taggable_id":"characters/SandorClegane",
              "taggable_type":"Tests\\\Setup\\\Models\\\Character"
           }
        ]';

        $taggables = json_decode($taggables, JSON_OBJECT_AS_ARRAY);

        foreach ($taggables as $taggable) {
            Taggable::insertOrIgnore($taggable);
        }
    }
}
