<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessRequest;

class CreateProcessRequestLocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->create('process_request_locks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('process_request_id')->nullable();
            $table->unsignedInteger('process_request_token_id')->nullable();
            $table->timestamps();
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
        Schema::connection($model->getConnectionName())->dropIfExists('process_request_locks');
    }
}
