<?php
namespace LaravelFreelancerNL\Aranguent\Tests\database\migrations;

use \Exception;
use Illuminate\Database\Migrations\Migration;
use LaravelFreelancerNL\Aranguent\Facades\Schema;
use LaravelFreelancerNL\Aranguent\Schema\Blueprint;


class CreateUsersCollection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Schema::create('users', function (Blueprint $collection) {
                $collection->primary('id');
                $collection->string('name')->index();
                $collection->string('email')->unique();
                $collection->timestamps();
            });
        } catch (Exception $e) {
            dd($e->getMessage());
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
