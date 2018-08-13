<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid');
            $table->unsignedInteger('process_id');

            //task description
            $table->text('title');
            $table->text('description')->nullable();

            //type definitions
            $table->enum('type', ['NORMAL', 'ADHOC', 'SUB_PROCESS', 'HIDDEN', 'GATEWAY_TO_GATEWAY', 'WEB_ENTRY_EVENT', 'END_MESSAGE_EVENT', 'START_MESSAGE_EVENT', 'INTERMEDIATE_THROW_MESSAGE_EVENT', 'INTERMEDIATE_CATCH_MESSAGE_EVENT', 'SCRIPT_TASK', 'START_TIMER_EVENT', 'INTERMEDIATE_CATCH_TIMER_EVENT', 'END_EMAIL_EVENT', 'INTERMEDIATE_THROW_EMAIL_EVENT', 'SERVICE_TASK'])->default('NORMAL');
            $table->enum('assign_type', ['BALANCED', 'MANUAL', 'EVALUATE', 'REPORT_TO', 'SELF_SERVICE', 'STATIC_MI', 'CANCEL_MI', 'MULTIPLE_INSTANCE', 'MULTIPLE_INSTANCE_VALUE_BASED'])->default('BALANCED');
            $table->enum('routing_type', ['NORMAL', 'FAST', 'AUTOMATIC'])->default('NORMAL');

            //variables assignment
            $table->string('priority_variable', 100)->default('');
            $table->string('assign_variable', 100)->default('@@SYS_NEXT_USER_TO_BE_ASSIGNED');
            $table->string('group_variable', 100)->nullable();

            //is it an start task
            $table->boolean('is_start_task')->default(false);

            //template screen showed when routing
            $table->string('routing_screen_template', 128)->nullable();

            //duration json options
            $table->json('timing_control_configuration')->nullable();
            /*************************************************************************************************
            the field timing_control_configuration contains the following

            Field          |   Type    |               values                              |   value default
            __________________________________________________________________________________
             * duration     |   float   |                                                   |   0
             * delay_type   |   enum    |['MINUTES', 'HOURS', 'DAYS']                       |   DAYS
             * temporizer   |   float   |                                                   |   0
             * type_day     |   enum    |['WORK_DAYS', 'CALENDAR_DAYS']                     |   WORK_DAYS
             * time_unit    |   enum    |['MINUTES', 'HOURS', 'DAYS', 'WEEKS', 'MONTHS']    |   DAYS
             *************************************************************************************************/

            //Options to run a script when you have a Self service timeout
            $table->unsignedInteger('script_id')->nullable();

            //self service json configuration
            $table->json('self_service_timeout_configuration')->nullable();
            /*************************************************************************************************
            the field self_service_timeout_configuration contains the following

            Field                       |   Type     |               values                             |   value default
            __________________________________________________________________________________
             * self_service_timeout     |   integer |                                                   |   0
             * self_service_time        |   integer |                                                   |   0
             * self_service_time_unit   |   string  |  ['MINUTES', 'HOURS', 'DAYS', 'WEEKS', 'MONTHS']  |   HOURS
             * self_service_execution   |   string  |  ['EVERY_TIME', 'ONCE']                           |   EVERY_TIME
             *************************************************************************************************/

            //title and description customized by the user and showed in the cases list
            $table->text('custom_title')->nullable();
            $table->text('custom_description')->nullable();

            $table->timestamps();

            // setup relationships of the task with processes and other tables
            $table->foreign('process_id')->references('id')->on('processes')->ondelete('cascade');
            // setup relationships of the task with scripts and other tables
            $table->foreign('script_id')->references('id')->on('scripts')->ondelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
