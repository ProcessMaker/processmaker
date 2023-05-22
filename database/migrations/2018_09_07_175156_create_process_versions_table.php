<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_versions', function (Blueprint $table) {
            // NOTE: Remember to update ProcessVersions when updating this table
            // Columns
            $table->increments('id');
            $table->unsignedInteger('process_id');
            $table->unsignedInteger('process_category_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->text('bpmn');
            $table->text('description');
            $table->string('name');
            $table->unsignedInteger('cancel_screen_id')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE']);
            $table->boolean('pause_timer_start')->default(0);
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_versions');
    }
}
