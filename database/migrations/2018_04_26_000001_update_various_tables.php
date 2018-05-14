<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to update the process design tables to version 4.0.0.
 *
 */
class UpdateVariousTables extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update Application table to have a foreign key on process_id
        // APPLICATION
        Schema::table('APPLICATION', function(Blueprint $table) {
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //PROCESS CASE TRACKER GENERAL CONFIGURATION
        Schema::table('CASE_TRACKER', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //PROCESS CASE TRACKER OBJECTS CONFIGURATION (CLASSIC)
        Schema::table('CASE_TRACKER_OBJECT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //DYNAFORMS
        Schema::table('dynaform', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //ELEMENT(GATEWAY, EVENT) TO DUMMY TASK RELATIONS
        Schema::table('ELEMENT_TASK_RELATION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //EMAIL EVENTS (CLASSIC)
        Schema::table('EMAIL_EVENT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //INPUT DOCUMENTS
        Schema::table('input_document', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //MESSAGE EVENT DEFINITIONS
        Schema::table('MESSAGE_EVENT_DEFINITION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //MESSAGE EVENT RELATIONS
        Schema::table('MESSAGE_EVENT_RELATION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //MESSAGE TYPES
        Schema::table('MESSAGE_TYPE', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //OBJECT PERMISSIONS
        Schema::table('OBJECT_PERMISSION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //OUTPUT DOCUMENTS
        Schema::table('OUTPUT_DOCUMENT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //PROCESS USERS (like SUPERVISORS)
        Schema::table('PROCESS_USER', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //ROUTES
        Schema::table('ROUTE', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //SCRIPT TASKS
        Schema::table('SCRIPT_TASK', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //STEPS
        Schema::table('STEP', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //STEP SUPERVISORS
        Schema::table('STEP_SUPERVISOR', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //SUB PROCESSES
        Schema::table('SUB_PROCESS', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //TIMER EVENTS
        Schema::table('TIMER_EVENT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //CASES DELAY FROM PAUSE/UNPAUSE/CANCEL
        Schema::table('APP_DELAY', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //CASES DELEGATIONS
        Schema::table('APP_DELEGATION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
        });

        //CASES HISTORY
        Schema::table('APP_HISTORY', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
        });

        //MESSAGE EVENTS THROUGH THE CASES
        Schema::table('MESSAGE_APPLICATION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
        });

        // Update Permission Roles to have foreign keys
        Schema::table('permission_role', function(Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('CASCADE');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('CASCADE');
        });

        // Update process table to have foregin keys
        Schema::table('processes', function(Blueprint $table) {
             $table->foreign('parent_process_id')->references('id')->on('processes')->onDelete('CASCADE');
             // @todo Update to have triggers id foreign keys once triggers table schema is updated
             // @todo Update to have category id foreign keys once process category table schema is updated
              $table->foreign('creator_user_id')->references('id')->on('users')->onDelete('CASCADE');
        });



    }
}
