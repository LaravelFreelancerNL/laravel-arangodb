<?php

use Illuminate\Database\Migrations\Migration;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;

class CreateChildrenEdgeTable extends Migration
{

    const EDGE_COLLECTION = 3;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'children',
            function (Blueprint $collection) {
                $collection->unique(['_from', '_to']);
            },
            ['type' => self::EDGE_COLLECTION]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('children');
    }
}
