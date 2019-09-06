<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataSources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('endpoints')->nullable(); // create, read, update, delete, list
            $table->json('mappings')->nullable(); // one to one key mapping with required flags {'apikey'=>'variable','required'=>true/false}
            $table->enum('authtype', ['NONE', 'BASIC', 'BEARER'])->default('NONE');
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->unsignedBigInteger('data_source_category_id');
            $table->timestamps();

            // Indexes
            $table->index('data_source_category_id');

            // Foreign keys
            $table->foreign('data_source_category_id')->references('id')->on('data_source_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_sources');
    }
}
