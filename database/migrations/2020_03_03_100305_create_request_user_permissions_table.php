<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\ProcessRequest;

class CreateRequestUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())
        ->create('request_user_permissions', function (Blueprint $table) {
            $table->unsignedInteger('request_id');
            $table->unsignedInteger('user_id');
            $table->boolean('can_view');
            $table->timestamps();

            $table->primary(['request_id', 'user_id']);
            $table->index(['request_id']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_user_permissions');
    }
}
