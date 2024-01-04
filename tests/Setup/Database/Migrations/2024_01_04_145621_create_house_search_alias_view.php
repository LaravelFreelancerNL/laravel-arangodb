<?php

use Illuminate\Database\Migrations\Migration;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;

class CreateHouseSearchAliasView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('houses', function (Blueprint $collection) {
            $collection->invertedIndex(
                [
                    'name',
                    'en.description',
                    'en.summary',
                ],
                'InvIdx',
            );

            $collection->invertedIndex(
                [
                    'name',
                    'en.description',
                    'en.summary',
                ],
                'TextEnInvIdx',
                [
                    "analyzer" => "text_en",
                    "features" => [
                        "frequency",
                        "position",
                        "norm"
                    ],
                    "includeAllFields" => true,
                    "searchField" => true,
                    "trackListPositions" => true,
                ]
            );
        });

        Schema::dropViewIfExists('house_search_alias_view');

        Schema::createView(
            'house_search_alias_view',
            [
                "indexes" => [
                    [
                        "collection" => "houses",
                        "index" => "TextEnInvIdx"
                    ]
                ],
            ],
            'search-alias'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropViewIfExists('house_search_alias_view');
    }
}
