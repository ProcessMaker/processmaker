<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
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
        foreach(Script::all() as $script) {
            if ($script->category) {
                $category = ScriptCategory::firstOrCreate(['name' => $script->category ]);
                $script->category()->associate($category);
            }
        }
        foreach(Screen::all() as $screen) {
            if ($screen->category) {
                $category = ScreenCategory::firstOrCreate(['name' => $screen->category]);
                $screen->category()->associate($category);
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
        Schema::table('scripts', function (Blueprint $table) {
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
        Schema::table('script_versions', function (Blueprint $table) {
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
        Schema::table('screens', function (Blueprint $table) {
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
        Schema::table('screen_versions', function (Blueprint $table) {
            $table->string('category', '100')->after('status')->nullable()->default(null);
        });
        foreach(Script::all() as $script) {
            $category = $script->category()->first();
            !$category ?: $script->category = $category->name;
        }
        foreach(Screen::all() as $screen) {
            $category = $screen->category()->first();
            !$category ?: $screen->category = $category->name;
        }
    }
}
