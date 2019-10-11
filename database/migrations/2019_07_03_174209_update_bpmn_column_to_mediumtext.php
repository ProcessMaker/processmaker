<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\Process;

class UpdateBpmnColumnToMediumtext extends Migration
{
    public function __construct()
    {
        // I guess this is still a bug in laravel 5.7 and doctrine
        // https://stackoverflow.com/questions/33140860/laravel-5-1-unknown-database-type-enum-requested
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new Process;
        Schema::connection($model->getConnectionName())->table('processes', function (Blueprint $table) {
            // A comment change is required
            // https://github.com/doctrine/dbal/issues/2566#issuecomment-480217999
            $table->mediumText('bpmn')->comment('up to 16M')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $model = new Process;
        Schema::connection($model->getConnectionName())->table('processes', function (Blueprint $table) {
            $table->text('bpmn')->comment('')->change();
        });
    }
}
