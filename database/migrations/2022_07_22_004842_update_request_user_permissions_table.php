<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_user_permissions', function (Blueprint $table) {
            $table->dropColumn('can_view');
            $table->unique(['request_id', 'user_id']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_user_permissions', function (Blueprint $table) {
            $table->boolean('can_view')->default(false);
            $table->dropUnique(['request_id', 'user_id']);
        });
    }
}
