<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasColumn('process_templates', 'updated_by')) {
            Schema::table('process_templates', function (Blueprint $table) {
                $table->unsignedInteger('updated_by')->nullable()->after('user_id');
            });
        }

        // Seed updated_by with the existing user_id value
        DB::table('process_templates')->update([
            'updated_by' => DB::raw('user_id'),
        ]);
    }

    public function down()
    {
        Schema::table('process_templates', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
    }
};
