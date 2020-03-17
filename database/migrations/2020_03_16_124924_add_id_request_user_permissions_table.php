<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\ProcessRequest;

class AddIdRequestUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('request_user_permissions', function (Blueprint $table) {
            $table->dropPrimary( ['request_id', 'user_id'] );
        });
        Schema::connection($model->getConnectionName())->table('request_user_permissions', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($model->getConnectionName())->table('request_user_permissions', function (Blueprint $table) {
            $table->dropColumn(['id']);
            $table->primary(['request_id', 'user_id']);
        });
    }
}
