<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cases_participated', function (Blueprint $table) {
            $table->index('case_number', 'cases_participated_case_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases_participated', function (Blueprint $table) {
            $table->dropIndex('cases_participated_case_number_index');
        });
    }
};
