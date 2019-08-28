<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\ProcessRequest;

class AddProcessRequestIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest;
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $model = new ProcessRequest;
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
            $table->dropIndex('process_requests_user_id_status_index');
            $table->dropIndex('process_requests_status_index');
        });
    }
}
