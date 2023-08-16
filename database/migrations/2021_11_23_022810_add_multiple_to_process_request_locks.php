<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessRequest;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_request_locks', function (Blueprint $table) {
            $table->json('request_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_request_locks', function (Blueprint $table) {
            $table->dropColumn('request_ids');
        });
    }
};
