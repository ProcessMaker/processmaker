<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\SettingsMenus;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings_menus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('menu_group')->nullable()->default('Undefined');
            $table->string('menu_group_icon')->nullable()->default('start');
            $table->integer('menu_group_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings_menus');
    }
};
