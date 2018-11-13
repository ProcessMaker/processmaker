<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            // Columns
            $table->increments('id');
            $table->unsignedInteger('process_id');
            $table->unsignedInteger('process_category_id')->nullable();
            $table->unsignedInteger('summary_screen_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->text('bpmn');
            $table->text('description');
            $table->string('name');
            $table->enum('status', ['ACTIVE', 'INACTIVE']);
            $table->timestamps();

            // Indexes
            $table->index('process_id');
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
