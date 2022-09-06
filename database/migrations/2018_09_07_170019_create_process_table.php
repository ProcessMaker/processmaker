<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->unsignedInteger('cancel_screen_id')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->boolean('pause_timer_start')->default(0);
            $table->softDeletes();
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
