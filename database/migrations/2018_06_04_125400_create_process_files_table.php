<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('PROCESS_FILES', function(Blueprint $table)
        {
            $table->integer('PRF_ID', true);
            $table->string('PRF_UID', 32);
            $table->unsignedInteger('process_id');
            $table->uuid('USR_UID');
            $table->uuid('PRF_UPDATE_USR_UID');
            $table->string('PRF_PATH', 256)->default('');
            $table->string('PRF_TYPE', 32)->nullable()->default('');
            $table->boolean('PRF_EDITABLE')->nullable()->default(1);
            $table->string('PRF_DRIVE', 32);
            $table->string('PRF_PATH_FOR_CLIENT');
            $table->dateTime('PRF_CREATE_DATE');
            $table->dateTime('PRF_UPDATE_DATE')->nullable();
            $table->unique(['process_id','PRF_PATH_FOR_CLIENT'], 'UQ_PRO_UID_PRF_PATH_FOR_CLIENT');

            // Setup relationship for process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('PROCESS_FILES');
    }
}
