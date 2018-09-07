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
            // columns
            $table->uuid('uuid');
            $table->uuid('process_uuid');
            $table->uuid('process_category_uuid');
            $table->string('name');
            $table->enum('status', ['ACTIVE', 'INACTIVE']);
            $table->text('bpmn');
            $table->timestamps();

            // indexes
            $table->primary('uuid');

            // foreign keys
            $table->foreign('process_uuid')->references('uuid')->on('processes')->onDelete('cascade');
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
        Schema::dropIfExists('process_versions');
    }
}
