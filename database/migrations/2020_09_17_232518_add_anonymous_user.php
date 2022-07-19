<?php

use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAnonymousUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system')->after('is_administrator')->default(false);
        });

        // Creating user moved to seeder

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->forceDelete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
}
