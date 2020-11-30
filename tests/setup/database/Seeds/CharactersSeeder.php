<?php

namespace Tests\Setup\Database\Seeds;

use Illuminate\Database\Seeder;
use Tests\Setup\Models\Character;

class CharactersSeeder extends Seeder
{
    /**
     * Run the database Seeds.
     *
     * @return void
     */
    public function run()
    {
        $characters = '[{ "_key": "NedStark", "name": "Ned", "surname": "Stark", "alive": true, "age": 41, '
            . '"traits": ["A","H","C","N","P"], "residence_key": "winterfell" },
            { "_key": "RobertBaratheon", "name": "Robert", "surname": "Baratheon", "alive": false, '
            . '"traits": ["A","H","C"], "residence_key": "winterfell" },
            { "_key": "JaimeLannister", "name": "Jaime", "surname": "Lannister", "alive": true, "age": 36, '
            . '"traits": ["A","F","B"], "residence_key": "the-red-keep" },
            { "_key": "CatelynStark", "name": "Catelyn", "surname": "Stark", "alive": false, "age": 40, '
            . '"traits": ["D","H","C"], "residence_key": "winterfell" },
            { "_key": "CerseiLannister", "name": "Cersei", "surname": "Lannister", "alive": true, "age": 36, '
            . '"traits": ["H","E","F"], "residence_key": "the-red-keep" },
            { "_key": "DaenerysTargaryen", "name": "Daenerys", "surname": "Targaryen", "alive": true, "age": 16, '
            . '"traits": ["D","H","C"], "residence_key": "winterfell" },
            { "_key": "JorahMormont", "name": "Jorah", "surname": "Mormont", "alive": false, '
            . '"traits": ["A","B","C","F"], "residence_key": "winterfell" },
            { "_key": "PetyrBaelish", "name": "Petyr", "surname": "Baelish", "alive": false, '
            . '"traits": ["E","G","F"], "residence_key": "the-red-keep" },
            { "_key": "ViserysTargaryen", "name": "Viserys", "surname": "Targaryen", "alive": false, '
            . '"traits": ["O","L","N"], "residence_key": null },
            { "_key": "JonSnow", "name": "Jon", "surname": "Snow", "alive": true, "age": 16, '
            . '"traits": ["A","B","C","F"], "residence_key": "winterfell" },
            { "_key": "SansaStark", "name": "Sansa", "surname": "Stark", "alive": true, "age": 13, '
            . '"traits": ["D","I","J"], "residence_key": "winterfell" },
            { "_key": "AryaStark", "name": "Arya", "surname": "Stark", "alive": true, "age": 11, '
            . '"traits": ["C","K","L"], "residence_key": "winterfell" },
            { "_key": "RobbStark", "name": "Robb", "surname": "Stark", "alive": false, '
            . '"traits": ["A","B","C","K"], "residence_key": "winterfell" },
            { "_key": "TheonGreyjoy", "name": "Theon", "surname": "Greyjoy", "alive": true, "age": 16, '
            . '"traits": ["E","R","K"], "residence_key": "winterfell" },
            { "_key": "BranStark", "name": "Bran", "surname": "Stark", "alive": true, "age": 10, '
            . '"traits": ["L","J"], "residence_key": "winterfell" },
            { "_key": "JoffreyBaratheon", "name": "Joffrey", "surname": "Baratheon", "alive": false, "age": 19, '
            . '"traits": ["I","L","O"], "residence_key": "the-red-keep" },
            { "_key": "SandorClegane", "name": "Sandor", "surname": "Clegane", "alive": true, '
            . '"traits": ["A","P","K","F"], "residence_key": "the-red-keep" },
            { "_key": "TyrionLannister", "name": "Tyrion", "surname": "Lannister", "alive": true, "age": 32, '
            . '"traits": ["F","K","M","N"], "residence_key": "the-red-keep" },
            { "_key": "KhalDrogo", "name": "Khal", "surname": "Drogo", "alive": false, '
            . '"traits": ["A","C","O","P"], "residence_key": "winterfell" },
            { "_key": "TywinLannister", "name": "Tywin", "surname": "Lannister", "alive": false, '
            . '"traits": ["O","M","H","F"], "residence_key": "the-red-keep" },
            { "_key": "DavosSeaworth", "name": "Davos", "surname": "Seaworth", "alive": true, "age": 49, '
            . '"traits": ["C","K","P","F"], "residence_key": "winterfell" },
            { "_key": "SamwellTarly", "name": "Samwell", "surname": "Tarly", "alive": true, "age": 17, '
            . '"traits": ["C","L","I"], "residence_key": "winterfell" },
            { "_key": "StannisBaratheon", "name": "Stannis", "surname": "Baratheon", "alive": false, '
            . '"traits": ["H","O","P","M"], "residence_key": "dragonstone" },
            { "_key": "Melisandre", "name": "Melisandre", "alive": true, '
            . '"traits": ["G","E","H"], "residence_key": "dragonstone" },
            { "_key": "MargaeryTyrell", "name": "Margaery", "surname": "Tyrell", "alive": false, '
            . '"traits": ["M","D","B"], "residence_key": "winterfell" },
            { "_key": "JeorMormont", "name": "Jeor", "surname": "Mormont", "alive": false, '
            . '"traits": ["C","H","M","P"], "residence_key": null },
            { "_key": "Bronn", "name": "Bronn", "alive": true, '
            . '"traits": ["K","E","C"], "residence_key": "king-s-landing" },
            { "_key": "Varys", "name": "Varys", "alive": true, '
            . '"traits": ["M","F","N","E"], "residence_key": "the-red-keep" },
            { "_key": "Shae", "name": "Shae", "alive": false, '
            . '"traits": ["M","D","G"], "residence_key": "the-red-keep" },
            { "_key": "TalisaMaegyr", "name": "Talisa", "surname": "Maegyr", "alive": false, '
            . '"traits": ["D","C","B"] },
            { "_key": "Gendry", "name": "Gendry", "alive": false, '
            . '"traits": ["K","C","A"], "residence_key": "king-s-landing"  },
            { "_key": "Ygritte", "name": "Ygritte", "alive": false, '
            . '"traits": ["A","P","K"], "residence_key": "beyond-the-wall" },
            { "_key": "TormundGiantsbane", "name": "Tormund", "surname": "Giantsbane", "alive": true, '
            . '"traits": ["C","P","A","I"], "residence_key": "beyond-the-wall" },
            { "_key": "Gilly", "name": "Gilly", "alive": true, '
            . '"traits": ["L","J"], "residence_key": "beyond-the-wall" },
            { "_key": "BrienneTarth", "name": "Brienne", "surname": "Tarth", "alive": true, "age": 32, '
            . '"traits": ["P","C","A","K"] },
            { "_key": "RamsayBolton", "name": "Ramsay", "surname": "Bolton", "alive": true, '
            . '"traits": ["E","O","G","A"] },
            { "_key": "EllariaSand", "name": "Ellaria", "surname": "Sand", "alive": true, '
            . '"traits": ["P","O","A","E"] },
            { "_key": "DaarioNaharis", "name": "Daario", "surname": "Naharis", "alive": true, '
            . '"traits": ["K","P","A"] },
            { "_key": "Missandei", "name": "Missandei", "alive": true, '
            . '"traits": ["D","L","C","M"] },
            { "_key": "TommenBaratheon", "name": "Tommen", "surname": "Baratheon", "alive": true, '
            . '"traits": ["I","L","B"], "residence_key": "the-red-keep" },
            { "_key": "JaqenHghar", "name": "Jaqen", "surname": "H\'ghar", "alive": true, '
            . '"traits": ["H","F","K"] },
            { "_key": "RooseBolton", "name": "Roose", "surname": "Bolton", "alive": true, '
            . '"traits": ["H","E","F","A"] },
            { "_key": "TheHighSparrow", "name": "The High Sparrow", "alive": true, '
            . '"traits": ["H","M","F","O"], "residence_key": "king-s-landing" }
            ]';

        $characters = json_decode($characters, JSON_OBJECT_AS_ARRAY);

        foreach ($characters as $character) {
            Character::insert($character);
        }
    }
}
