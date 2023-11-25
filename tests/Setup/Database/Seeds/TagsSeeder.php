<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Tests\Setup\Models\Tag;

class TagsSeeder extends Seeder
{
    /**
     * Run the database Seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = '[
           {
              "id":"A",
              "en":"strong",
              "de":"stark"
           },
           {
              "id":"B",
              "en":"polite",
              "de":"freundlich"
           },
           {
              "id":"C",
              "en":"loyal",
              "de":"loyal"
           },
           {
              "id":"D",
              "en":"beautiful",
              "de":"schön"
           },
           {
              "id":"E",
              "en":"sneaky",
              "de":"hinterlistig"
           },
           {
              "id":"F",
              "en":"experienced",
              "de":"erfahren"
           },
           {
              "id":"G",
              "en":"corrupt",
              "de":"korrupt"
           },
           {
              "id":"H",
              "en":"powerful",
              "de":"einflussreich"
           },
           {
              "id":"I",
              "en":"naive",
              "de":"naiv"
           },
           {
              "id":"J",
              "en":"unmarried",
              "de":"unverheiratet"
           },
           {
              "id":"K",
              "en":"skillful",
              "de":"geschickt"
           },
           {
              "id":"L",
              "en":"young",
              "de":"jung"
           },
           {
              "id":"M",
              "en":"smart",
              "de":"klug"
           },
           {
              "id":"N",
              "en":"rational",
              "de":"rational"
           },
           {
              "id":"O",
              "en":"ruthless",
              "de":"skrupellos"
           },
           {
              "id":"P",
              "en":"brave",
              "de":"mutig"
           },
           {
              "id":"Q",
              "en":"mighty",
              "de":"mächtig"
           },
           {
              "id":"R",
              "en":"weak",
              "de":"schwach"
           },
           {
              "id":"S",
              "en":"wild",
              "de":"wild"
           }
        ]';

        $tags = json_decode($tags, JSON_OBJECT_AS_ARRAY);
        foreach ($tags as $tag) {
            Tag::insertOrIgnore($tag);
        }
    }
}
