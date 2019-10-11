<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;

class CreateCategoryAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('assignable');
            $table->morphs('category');
            $table->timestamps();
        });

        // Transition existing assignments
        foreach (Process::all() as $process) {
            $process->categories()->save([
                'category_type' => ProcessCategory::class,
                'category_id' => $process->category_id,
            ]);
        }
        foreach (Screen::all() as $screen) {
            $screen->categories()->save([
                'category_type' => ScreenCategory::class,
                'category_id' => $process->category_id,
            ]);
        }
        foreach (Script::all() as $script) {
            $script->categories()->save([
                'category_type' => ScriptCategory::class,
                'category_id' => $process->category_id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_assignments');
    }
}
