<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processes', function (Blueprint $table) {
            // Columns
            $table->increments('id');
            $table->unsignedInteger('process_category_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->text('bpmn');
            $table->text('description');
            $table->string('name');
            $table->enum('status', ['ACTIVE', 'INACTIVE']);
            $table->timestamps();

            // Indexes
            $table->index('process_category_id');

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('process_category_id')->references('id')->on('process_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('processes');
    }
}
