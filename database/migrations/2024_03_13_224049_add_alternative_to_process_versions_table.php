<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'process_versions',
            function (Blueprint $table) {
                $table->enum('alternative', ['A', 'B'])->default('A');
            }
        );
        Schema::table(
            'processes',
            function (Blueprint $table) {
                $table->enum('alternative', ['A', 'B'])->default('A');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'process_versions',
            function (Blueprint $table) {
                $table->dropColumn('alternative');
            }
        );
        Schema::table(
            'processes',
            function (Blueprint $table) {
                $table->dropColumn('alternative');
            }
        );
    }
};
