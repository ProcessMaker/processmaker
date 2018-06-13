<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('languages', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('uid', 4)->unique();
            $table->string('location', 4)->default('');
            $table->string('name', 30)->default('');
            $table->string('native_name', 30)->default('');
            $table->char('direction', 1)->default('L');
            $table->integer('weight')->default(0);
            $table->boolean('enabled')->default(true);
            $table->string('calendar', 30)->default('GREGORIAN');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('languages');
    }
}
