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
        Schema::create('inbox_rule_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inbox_rule_id');
            $table->integer('task_id');
            $table->json('inbox_rule_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_rule_logs');
    }
};
