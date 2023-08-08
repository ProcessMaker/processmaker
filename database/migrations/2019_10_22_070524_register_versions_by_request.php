<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessVersion;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
            $table->integer('process_version_id')->nullable();
            $table->json('versions')->nullable();
        });
        foreach (ProcessRequest::all() as $request) {
            $request->setCurrentVersions()->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
            $table->dropColumn('process_version_id');
        });
    }
};
