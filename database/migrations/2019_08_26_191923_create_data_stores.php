<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataStores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_stores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('endpoints')->nullable(); // create, read, update, delete, list
            $table->json('mappings')->nullable(); // one to one key mapping with required flags {'apikey'=>'variable','required'=>true/false}            
            $table->enum('authtype', ['NONE', 'BASIC', 'BEARER'])->default('NONE');
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->unsignedBigInteger('data_store_category_id');
            $table->timestamps();

            // Indexes
            $table->index('data_store_category_id');

            // Foreign keys
            $table->foreign('data_store_category_id')->references('id')->on('data_store_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_stores');
    }
}
