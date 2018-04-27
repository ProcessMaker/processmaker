<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to update the process design tables to version 4.0.0.
 *
 */
class UpdateProcessTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('BPMN_DIAGRAM', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_diagram_project');
            $table->dropColumn('PRJ_UID');
        });
        Schema::table('BPMN_BOUND', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_bound_project');
            $table->dropColumn('PRJ_UID');
        });
        Schema::table('BPMN_ACTIVITY', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_activity_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_activity_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('BPMN_EVENT', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_event_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_event_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('BPMN_GATEWAY', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_gateway_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_gateway_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('BPMN_FLOW', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_flow_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_flow_diagram');
        });
        Schema::table('BPMN_ARTIFACT', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_artifact_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_artifact_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('EMAIL_EVENT', function(Blueprint $table) {
            $table->dropColumn('PRJ_UID');
        });

        //DELETE THE PROCESS DEFINITION WHEN THE PROCESS IS DELETED

        //BPMN ACTIVITIES
        Schema::table('BPMN_ACTIVITY', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN ARTIFACTS
        Schema::table('BPMN_ARTIFACT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN BOUNDS
        Schema::table('BPMN_BOUND', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN DATA SHAPES
        Schema::table('BPMN_DATA', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN DIAGRAM(S)
        Schema::table('BPMN_DIAGRAM', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN DOCUMENTATIONS
        Schema::table('BPMN_DOCUMENTATION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN EVENTS
        Schema::table('BPMN_EVENT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN EXTENSIONS
        Schema::table('BPMN_EXTENSION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN FLOWS
        Schema::table('BPMN_FLOW', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN GATEWAYS
        Schema::table('BPMN_GATEWAY', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN LANES
        Schema::table('BPMN_LANE', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_lane_project');
            $table->dropColumn('PRJ_UID');
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //BPMN LANESETS
        Schema::table('BPMN_LANESET', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_laneset_project');
            $table->dropColumn('PRJ_UID');
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
            $table->dropColumn('LNS_STATE');
        });

        //BPMN PARTICIPANTS
        Schema::table('BPMN_PARTICIPANT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //PROCESS SCHEDULED START CASES (CLASSIC)
        Schema::table('CASE_SCHEDULER', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //LOG OF PROCESS CASES SCHEDULER
        Schema::table('LOG_CASES_SCHEDULER', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
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
        Schema::table('DYNAFORM', function(Blueprint $table) {
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

        //EVENTS
        Schema::table('EVENT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //GATEWAYS
        Schema::table('GATEWAY', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //INPUT DOCUMENTS
        Schema::table('INPUT_DOCUMENT', function(Blueprint $table) {
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

        //PROCESS FILES
        Schema::table('PROCESS_FILES', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //PROCESS USERS (like SUPERVISORS)
        Schema::table('PROCESS_USER', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //STATISTICAL INFORMATION AND KPIs OF THE PROCESS
        Schema::table('PRO_REPORTING', function(Blueprint $table) {
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

        //STAGES (CLASSIC)
        Schema::table('STAGE', function(Blueprint $table) {
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

        //SWIMLANES ELEMENTS
        Schema::table('SWIMLANES_ELEMENTS', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //TASKS
        Schema::table('TASK', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //TIMER EVENTS
        Schema::table('TIMER_EVENT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //TRIGGERS
        Schema::table('TRIGGERS', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //USR REPORTING
        Schema::table('USR_REPORTING', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //WEB ENTRIES
        Schema::table('WEB_ENTRY', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //WEB ENTRY EVENTS
        Schema::table('WEB_ENTRY_EVENT', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //CASES ASSIGNMENT, SELF SERVICE VALUES
        Schema::table('APP_ASSIGN_SELF_SERVICE_VALUE', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('CASCADE');
        });

        //CASES LIST CACHE
        Schema::table('APP_CACHE_VIEW', function(Blueprint $table) {
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

        //LIST OF CANCELED CASES
        Schema::table('LIST_CANCELED', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //LIST OF COMPLETED CASES
        Schema::table('LIST_COMPLETED', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //LIST INBOX
        Schema::table('LIST_INBOX', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //HISTORIC OF PARTICIPATION
        Schema::table('LIST_PARTICIPATED_HISTORY', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //LIST OF THE LAST PARTICIPATED IN EACH CASE
        Schema::table('LIST_PARTICIPATED_LAST', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //LIST OF PAUSED CASES
        Schema::table('LIST_PAUSED', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //LIST OF UNASSIGNED CASES
        Schema::table('LIST_UNASSIGNED', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
 
        });

        //MESSAGE EVENTS THROUGH THE CASES
        Schema::table('MESSAGE_APPLICATION', function(Blueprint $table) {
            $table->unsignedInteger('process_id')->nullable();
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('RESTRICT');
        });
    }
}
