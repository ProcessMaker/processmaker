<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Process;

return new class extends Migration {
    public function __construct()
    {

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
};
