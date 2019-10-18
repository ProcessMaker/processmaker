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
            $process->categories()->sync([$process->process_category_id]);
        }
        foreach (Screen::all() as $screen) {
            $screen->categories()->sync([$screen->screen_category_id]);
        }
        foreach (Script::all() as $script) {
            $script->categories()->sync([$script->script_category_id]);
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
