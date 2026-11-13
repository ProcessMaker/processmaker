<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            $table->string('execution_mode', 16)
                ->default('bare')
                ->after('allow_in_process');
        });

        Schema::table('script_versions', function (Blueprint $table): void {
            $table->string('execution_mode', 16)
                ->default('bare')
                ->after('allow_in_process');
        });
    }

    public function down(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            $table->dropColumn('execution_mode');
        });

        Schema::table('script_versions', function (Blueprint $table): void {
            $table->dropColumn('execution_mode');
        });
    }
};
