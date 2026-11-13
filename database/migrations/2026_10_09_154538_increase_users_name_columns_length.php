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
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname', 255)->nullable()->change();
            $table->string('lastname', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally left empty: restoring VARCHAR(50) could truncate or fail
     * on existing rows with longer names.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
