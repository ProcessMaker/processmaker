<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbox_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->dateTime('end_date');
            $table->integer('saved_search_id')->nullable();
            $table->integer('process_request_token_id')->nullable();
            $table->boolean('mark_as_priority')->default(false);
            $table->integer('reasign_to_user_id')->nullable();
            $table->boolean('fill_data')->default(false);
            $table->json('submit_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_rules');
    }
};
