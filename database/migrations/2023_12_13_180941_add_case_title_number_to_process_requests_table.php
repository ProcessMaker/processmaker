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
        Schema::table('process_requests', function (Blueprint $table) {
            $table->unsignedInteger('case_number')->nullable();
            $table->string('case_title', 200)->nullable();
            $table->text('case_title_formatted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_requests', function (Blueprint $table) {
            $table->dropColumn('case_number');
            $table->dropColumn('case_title');
            $table->dropColumn('case_title_formatted');
        });
    }
};
