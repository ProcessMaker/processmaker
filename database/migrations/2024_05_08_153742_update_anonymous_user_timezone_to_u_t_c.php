<?php

use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\User;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::where('username', '_pm4_anon_user')->update([
            'timezone' => 'UTC'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('username', '_pm4_anon_user')->update([
            'timezone' => 'America/Los_Angeles'
        ]);
    }
};
