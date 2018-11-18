<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Enums\ActiveType;

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
            $table->text('bpmn');
            $table->string('name');
            $table->unsignedInteger('process_category_id');
            $table->unsignedInteger('process_id');
            $table->enum('status', ActiveType::getValues())->default(ActiveType::ACTIVE);
            $table->timestamps();

            // Indexes
            $table->index('process_id');

            // Foreign keys
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
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
        Schema::dropIfExists('process_versions');
    }
}
