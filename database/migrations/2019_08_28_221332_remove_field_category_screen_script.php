<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;

class RemoveFieldCategoryScreenScript extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Script::all() as $script) {
            if ($script->category) {
                $category = ScriptCategory::firstOrCreate(['name' => $script->category]);
                $script->category()->associate($category);
                $script->save();
            }
        }
        foreach (Screen::all() as $screen) {
            if ($screen->category) {
                $category = ScreenCategory::firstOrCreate(['name' => $screen->category]);
                $screen->category()->associate($category);
                $screen->save();
            }
        }
        Schema::table('scripts', function (Blueprint $table) {
            $table->dropColumn('category');
        });
        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('screens', function (Blueprint $table) {
            $table->dropColumn('category');
        });
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //  We don't need to recreate the columns in the down method.
    }
}
