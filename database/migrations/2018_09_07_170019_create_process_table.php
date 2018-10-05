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
            $table->uuid('uuid');
            $table->uuid('process_category_uuid')->nullable();
            $table->uuid('user_uuid');
            $table->text('bpmn');
            $table->text('description');
            $table->string('name');
            $table->enum('status', ['ACTIVE', 'INACTIVE']);
            $table->timestamps();

            // Indexes
            $table->primary('uuid');
            $table->index('process_category_uuid');

            // Foreign keys
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');
            $table->foreign('process_category_uuid')->references('uuid')->on('process_categories')->onDelete('cascade');
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
