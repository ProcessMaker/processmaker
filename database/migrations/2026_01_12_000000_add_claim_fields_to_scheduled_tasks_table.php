<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->string('claimed_by', 36)->nullable()->after('configuration');
            $table->dateTime('claimed_at')->nullable()->after('claimed_by');
            
            // Index for faster queries when claiming tasks
            $table->index(['claimed_by', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->dropIndex(['claimed_by', 'claimed_at']);
            $table->dropColumn(['claimed_by', 'claimed_at']);
        });
    }
};
