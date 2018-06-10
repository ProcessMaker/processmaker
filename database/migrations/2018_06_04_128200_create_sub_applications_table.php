<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSubApplicationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_applications', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid')->unique();
            $table->integer('application_id')->unsigned();
            $table->integer('del_index_parent')->default(0);
            $table->integer('del_thread_parent')->default(0);
            $table->string('status', 32)->default('');
            $table->text('values_out');
            $table->text('values_in')->nullable();
            $table->dateTime('init_date')->nullable();
            $table->dateTime('finish_date')->nullable();

            // Setup relationship for Application we belong to
            $table->foreign('application_id')->references('id')->on('APPLICATION')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sub_applications');
    }

}
