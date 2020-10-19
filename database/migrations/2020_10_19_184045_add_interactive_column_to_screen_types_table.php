<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ScreenType;

class AddInteractiveColumnToScreenTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('screen_types', function (Blueprint $table) {
            $table->boolean('is_interactive')->default(false);            
        });

        $rows = DB::table('screen_types')->get(['id', 'name']);
        foreach ($rows as $row) {
            switch ($row->name) {
                case 'FORM':
                case 'ADVANCED':
                case 'CONVERSATIONAL':
                    DB::table('screen_types')->where('id', $row->id)->update(['is_interactive' => true]);
                    break;
            }    
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('screen_types', function (Blueprint $table) {
            $table->dropColumn('is_interactive');
        });
    }
}
