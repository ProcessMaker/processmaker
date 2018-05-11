<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDYNAFORMTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dynaform', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->text('title', 16777215);
            $table->text('description', 16777215)->nullable();
            $table->string('type', 20)->default('form');
            $table->text('content', 16777215)->nullable();
            $table->text('label', 16777215)->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dynaform');
    }

}
