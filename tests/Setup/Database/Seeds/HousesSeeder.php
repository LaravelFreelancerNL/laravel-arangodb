<?php

namespace Tests\Setup\Database\Seeds;

use Illuminate\Database\Seeder;
use Tests\Setup\Models\House;

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
        "_key": "lannister",
        "name": "Lannister",
        "location_id": "king-s-landing",
        "en": {
            "description": "House Lannister of Casterly Rock is one of the Great Houses of Westeros, one of its richest and most powerful families and one of its oldest dynasties. It was also the royal House of the Seven Kingdoms following the extinction of House Baratheon of King\'s Landing, which had been their puppet House during the War of the Five Kings, for a brief stint of time until House Targaryen took back the Iron Throne in Daenerys Targaryen\'s war for Westeros."
        }
    },
    {
        "_key": "stark",
        "name": "Stark",
        "location_id": "winterfell",
        "en": {
            "description": "House Stark of Winterfell is a Great House of Westeros and the royal house of the Kingdom of the North. They rule over the vast region known as the North from their seat in Winterfell. It is one of the oldest lines of Westerosi nobility by far, claiming a line of descent stretching back over eight thousand years. Before the Targaryen conquest, as well as during the War of the Five Kings and early on in Daenerys Targaryen\'s war for Westeros, the leaders of House Stark ruled over the region as the Kings in the North."
        }
    },
    {
        "_key": "targaryan",
        "name": "Targaryan",
        "location_id": "dragonstone",
        "en": {
            "coat-of-arms": "A three-headed dragon breathing flames, red on black (Sable, a dragon thrice-headed gules)",
            "description": "House Targaryen of Dragonstone is an exiled Great House of Westeros and the former royal House of the Seven Kingdoms. House Targaryen conquered and unified the realm before it was deposed during Robert\'s Rebellion and House Baratheon replaced it as the new royal House. The two surviving Targaryens, Viserys and Daenerys, fled into exile to the Free Cities of Essos across the Narrow Sea. House Lannister replaced House Baratheon as the royal House following the destruction of the Great Sept of Baelor, but the realm was reconquered by Daenerys Targaryen, retaking the Iron Throne following the Battle of King\'s Landing. After she laid waste to a surrendered King\'s Landing, Daenerys was assassinated by Jon Snow to prevent further destruction. Jon became the last known living member of House Targaryen and his identity as the son of Rhaegar Targaryen is kept hidden from Westeros. He is exiled to the Night\'s Watch for the assassination of Daenerys. The bloodline of House Targaryen also still exists in various houses, such as House Baratheon, House Velaryon, and House Martell.",
            "words": "Fire and Blood"
        }
    }
]';

        $houses = json_decode($houses, JSON_OBJECT_AS_ARRAY);

        foreach ($houses as $house) {
            House::insertOrIgnore($house);
        }
    }
}
