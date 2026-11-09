<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MCP system skills (and other large setting payloads) exceed MySQL TEXT (64KB).
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('config')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('config')->nullable()->change();
        });
    }
};
