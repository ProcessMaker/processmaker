<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.enable_external_connection')) {
            return;
        }
        Schema::table('process_requests', function (Blueprint $table) {
            //Foreign keys
            //If the collaboration is deleted the request stays without collaboration
            $table->foreign('process_collaboration_id')
                ->references('id')->on('process_collaborations')
                ->onDelete('set null');
            //A process can not be deleted if it has requests
            $table->foreign('process_id')
                ->references('id')->on('processes')
                ->onDelete('restrict');
            //An user can not be deleted if it has requests
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('restrict');
            //A request delete child requests in cascade
            $table->foreign('parent_request_id')
                ->references('id')->on('process_requests')
                ->onDelete('cascade');
        });

        Schema::table('comments', function (Blueprint $table) {
            //Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('process_request_tokens', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('process_request_id')->references('id')->on('process_requests')->onDelete('cascade');
            $table->foreign('subprocess_request_id')->references('id')->on('process_requests')->onDelete('cascade');
        });

        Schema::table('scheduled_tasks', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('process_request_id')->references('id')->on('process_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('database.enable_external_connection')) {
            return;
        }
        Schema::table('scheduled_tasks', function (Blueprint $table) {
            // Foreign keys
            $table->dropForeign('scheduled_tasks_process_request_id_foreign');
        });
        Schema::table('process_request_tokens', function (Blueprint $table) {
            // Foreign keys
            $table->dropForeign('process_request_tokens_subprocess_request_id_foreign');
            $table->dropForeign('process_request_tokens_process_request_id_foreign');
        });

        Schema::table('comments', function(Blueprint $table){
            $table->dropForeign('comments_user_id_foreign');
        });

        Schema::table('process_requests', function(Blueprint $table){
            $table->dropForeign('process_requests_parent_request_id_foreign');
            $table->dropForeign('process_requests_user_id_foreign');
            $table->dropForeign('process_requests_process_id_foreign');
            $table->dropForeign('process_requests_process_collaboration_id_foreign');
        });
    }
}
