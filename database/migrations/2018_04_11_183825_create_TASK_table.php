<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTASKTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid');
            $table->unsignedInteger('process_id');
            $table->text('title', 16777215);
            $table->text('description', 16777215)->nullable();
            $table->text('def_title', 16777215)->nullable();
            $table->text('def_subject_message', 16777215)->nullable();
            $table->text('def_proc_code', 16777215)->nullable();
            $table->text('def_message', 16777215)->nullable();
            $table->text('def_description', 16777215)->nullable();
            $table->string('type', 50)->default('normal');
            $table->float('duration', 10, 0)->default(0);
            $table->string('delay_type', 30)->default('');
            $table->float('temporizer', 10, 0)->default(0);
            $table->char('type_day', 1)->default(1);
            $table->string('timeunit', 20)->default('days');
            $table->string('alert', 20)->default('false');
            $table->string('priority_variable', 100)->default('');
            $table->string('assign_type', 30)->default('balanced');
            $table->string('assign_variable', 100)->default('@@sys_next_user_to_be_assigned');
            $table->string('group_variable', 100)->nullable();
            $table->string('mi_instance_variable', 100)->default('@@sys_var_total_instance');
            $table->string('mi_complete_variable', 100)->default('@@sys_var_total_instances_complete');
            $table->string('assign_location', 20)->default('false');
            $table->string('assign_location_adhoc', 20)->default('false');
            $table->string('transfer_fly', 20)->default('false');
            $table->string('last_assigned', 32)->default('0');
            $table->string('user', 32)->default('0');
            $table->string('can_upload', 20)->default('false');
            $table->string('view_upload', 20)->default('false');
            $table->string('view_additional_documentation', 20)->default('false');
            $table->string('can_cancel', 20)->default('false');
            $table->string('owner_app', 32)->default('');
            $table->string('stg_uid', 32)->default('');
            $table->string('can_pause', 20)->default('false');
            $table->string('can_send_message', 20)->default('true');
            $table->string('can_delete_docs', 20)->default('false');
            $table->string('self_service', 20)->default('false');
            $table->string('start', 20)->default('false');
            $table->string('to_last_user', 20)->default('false');
            $table->string('send_last_email', 20)->default('true');
            $table->string('derivation', 100)->default('normal');
            $table->integer('posx')->default(0);
            $table->integer('posy')->default(0);
            $table->integer('width')->default(110);
            $table->integer('height')->default(60);
            $table->string('color', 32)->default('');
            $table->string('evn_uid', 32)->default('');
            $table->string('boundary', 32)->default('');
            $table->string('derivation_screen_tpl', 128)->nullable()->default('');
            $table->integer('selfservice_timeout')->nullable()->default(0);
            $table->integer('selfservice_time')->nullable()->default(0);
            $table->string('selfservice_time_unit', 15)->nullable()->default('');
            $table->string('selfservice_trigger_uid', 32)->nullable()->default('');
            $table->string('selfservice_execution', 15)->nullable()->default('every_time');
            $table->integer('not_email_from_format')->nullable()->default(0);
            $table->string('offline', 20)->default('false');
            $table->string('email_server_uid', 32)->nullable()->default('');
            $table->string('auto_root', 20)->default('false');
            $table->string('receive_server_uid', 32)->nullable()->default('');
            $table->string('receive_last_email', 20)->default('false');
            $table->integer('receive_email_from_format')->nullable()->default(0);
            $table->string('receive_message_type', 20)->default('text');
            $table->string('receive_message_template', 100)->default('alert_message.html');
            $table->text('receive_subject_message', 16777215)->nullable();
            $table->text('receive_message', 16777215)->nullable();
            $table->timestamps();

            // setup relationship for process we belong to
            $table->foreign('process_id')->references('id')->on('processes')->ondelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('task');
    }

}
