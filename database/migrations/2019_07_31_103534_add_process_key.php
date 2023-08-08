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
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->boolean('is_valid')->after('status')->default(true);
            $table->string('package_key', '100')->after('is_valid')->index()->nullable();
        });

        Schema::table('process_versions', function (Blueprint $table) {
            $table->boolean('is_valid')->after('status')->default(true);
            $table->string('package_key', '100')->after('is_valid')->index()->nullable();
        });

        Schema::table('process_categories', function (Blueprint $table) {
            $table->boolean('is_system')->after('status')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
